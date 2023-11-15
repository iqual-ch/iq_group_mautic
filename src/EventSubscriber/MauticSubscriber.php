<?php

namespace Drupal\iq_group_mautic\EventSubscriber;

use Drupal\iq_group\Event\IqGroupEvent;
use Drupal\iq_group\IqGroupEvents;
use Drupal\iq_group_mautic\Event\IqGroupMauticEvent;
use Drupal\mautic_api\MauticApiServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to handle mautic events dispatched by iq_group module.
 */
class MauticSubscriber implements EventSubscriberInterface {
  /**
   * Mautic API Service.
   *
   * @var \Drupal\mautic_api\MauticApiServiceInterface
   */
  protected $mauticApiService;

  /**
   * General group of iq_group module setup (ignored in mautic).
   *
   * @var int
   */
  protected $preferencesGeneralGroup;

  /**
   * OrderReceiptSubscriber constructor.
   *
   * @param \Drupal\mautic_api\MauticApiServiceInterface $mautic_api_service
   *   Mautic API Service.
   */
  public function __construct(MauticApiServiceInterface $mautic_api_service) {
    $this->mauticApiService = $mautic_api_service;
    $this->preferencesGeneralGroup = \Drupal::config('iq_group.settings')->get('general_group_id');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      IqGroupEvents::USER_PROFILE_UPDATE => [['updateMauticContact', 300]],
      IqGroupEvents::USER_PROFILE_DELETE => [['deleteMauticContact', 300]],
    ];
  }

  /**
   * Create or update a Mautic contact.
   *
   * @param \Drupal\iq_group\Event\IqGroupEvent $event
   *   The event.
   */
  public function updateMauticContact(IqGroupEvent $event) {
    if ($event && $event->getUser()->id()) {
      $user = $event->getUser();

      $mautic_id = $user->field_iq_group_mautic_id->value;

      $email = $user->getEmail();

      // Add base data.
      $base_address_values = $user->get('field_iq_user_base_address')->getValue();
      $base_address = !empty($base_address_values) ? reset($base_address_values) : FALSE;
      $profile_data = [
        "drupal_id" => $user->id(),
        "firstname" => ($base_address) ? $base_address['given_name'] : "",
        "lastname" => ($base_address) ? $base_address['family_name'] : "",
        "token" => $user->field_iq_group_user_token->value,
        "preferred_locale" => $user->preferred_langcode->value,
      ];

      // Add tags data if available.
      if ($user->hasField('field_iq_group_tags')) {
        $profile_tags = [];

        // Add new tags if set.
        if (!$user->get('field_iq_group_tags')->isEmpty()) {
          foreach ($user->field_iq_group_tags as $tags) {
            if ($tags->entity) {
              $profile_tags[] = $tags->entity->label();
            }
          }
        }

        // Check previous user data if tags were removed
        // then remove them with the "-" prefix from Mautic.
        if (!$user->original->get('field_iq_group_tags')->isEmpty()) {
          foreach ($user->original->field_iq_group_tags as $tags) {
            if ($tags->entity) {
              if (array_search($tags->entity->label(), $profile_tags) === FALSE) {
                $profile_tags[] = "-" . $tags->entity->label();
              }
            }
          }
        }

        $profile_data["tags"] = $profile_tags;
      }

      // Add branches data if available.
      if ($user->hasField('field_iq_group_branches') && !$user->get('field_iq_group_branches')->isEmpty()) {
        $profile_data["branches"] = array_filter(array_column($user->field_iq_group_branches->getValue(), 'target_id'));
      }

      // Add product data if available.
      if ($user->hasField('field_iq_group_products') && !$user->get('field_iq_group_products')->isEmpty()) {
        $profile_data["products"] = array_filter(array_column($user->field_iq_group_products->getValue(), 'target_id'));
      }

      // Add prefences data if available.
      if ($user->hasField('field_iq_group_preferences') && !$user->get('field_iq_group_preferences')->isEmpty()) {
        $profile_data["preferences"] = array_filter(array_column($user->field_iq_group_preferences->getValue(), 'target_id'));

        // Remove general group from preferences if present.
        $generalGroupKey = array_search($this->preferencesGeneralGroup, $profile_data["preferences"]);
        if ($generalGroupKey !== FALSE) {
          unset($profile_data["preferences"][$generalGroupKey]);
        }
      }

      // Dispatch event so profile data can be further modified before sync.
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(new IqGroupMauticEvent($user, $profile_data), IqGroupMauticEvent::MAUTIC_CONTACT_UPDATE);

      // Create new Mautic contact if user hasn't been previously identified.
      if (empty($mautic_id) || $mautic_id == 0) {
        // Create new Mautic contact.
        $contact = $this->mauticApiService->createContact($email, $profile_data);

      }
      else {
        // Update existing Mautic contact.
        $profile_data["email"] = $email;

        $contact = $this->mauticApiService->editContact($mautic_id, $profile_data, TRUE);
      }

      // Check if a valid response and Mautic ID was returned.
      if ($contact && $contact["id"] && $contact["id"] > 0) {
        \Drupal::logger('iq_group_mautic')->notice('Updated mautic contact ' . $contact["id"] . ' of user ' . $user->id());

        // Save Mautic ID to Drupal user.
        $user->set('field_iq_group_mautic_id', $contact["id"]);
      }
      else {
        // Log failure.
        \Drupal::logger('iq_group_mautic')->notice('Mautic update failed for user ' . $user->id());
      }
    }
  }

  /**
   * Delete a Mautic contact.
   *
   * @param \Drupal\iq_group\Event\IqGroupEvent $event
   *   The event.
   */
  public function deleteMauticContact(IqGroupEvent $event) {
    if ($event && $event->getUser()->id()) {
      \Drupal::logger('iq_group_mautic')->notice('Mautic delete event triggered for ' . $event->getUser()->id());

      $user = $event->getUser();

      $mautic_id = $user->field_iq_group_mautic_id->value;

      if (!empty($mautic_id) || $mautic_id != 0) {
        $contact = $this->mauticApiService->deleteContact($mautic_id);
      }
    }
  }

}
