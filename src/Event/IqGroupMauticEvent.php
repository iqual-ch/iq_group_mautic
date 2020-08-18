<?php

namespace Drupal\iq_group_mautic\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a user is synchronized to mautic.
 */
class IqGroupMauticEvent extends Event {


  const MAUTIC_CONTACT_UPDATE = 'iq_group_mautic.mauticContactUpdate';

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
  public $profile_data = NULL;

  /**
   * Constructs the object.
   *
   * @param mixed
   *   The user and his profile data.
   */
  public function __construct(&$user, &$profile_data) {
    $this->user = &$user;
    $this->profile_data = &$profile_data;
  }

  /**
   * Returns the user.
   *
   * @return mixed
   */
  public function &getUser() {
    return $this->user;
  }

  /**
   * Returns the profile data of the user.
   *
   * @return mixed
   */
  public function &getProfileData() {
    return $this->profile_data;
  }

}
