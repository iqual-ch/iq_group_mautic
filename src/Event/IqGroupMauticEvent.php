<?php

namespace Drupal\iq_group_mautic\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that is fired when a user is synchronized to mautic.
 */
class IqGroupMauticEvent extends Event {


  public const MAUTIC_CONTACT_UPDATE = 'iq_group_mautic.mauticContactUpdate';

  /**
   * The user data of the event.
   *
   * @var mixed
   */
  public $user = NULL;

  /**
   * The profile data of the user of the event.
   *
   * @var mixed
   */
  public $profileData = NULL;

  /**
   * Constructs the object.
   *
   * @param mixed $user
   *   The user.
   * @param mixed $profileData
   *   The user's profile data.
   */
  public function __construct(&$user, &$profileData) {
    $this->user = &$user;
    $this->profileData = &$profileData;
  }

  /**
   * Returns the user.
   *
   * @return mixed
   *   The user.
   */
  public function &getUser() {
    return $this->user;
  }

  /**
   * Returns the profile data of the user.
   *
   * @return mixed
   *   The user's profile data.
   */
  public function &getProfileData() {
    return $this->profileData;
  }

}
