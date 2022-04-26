<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Test \Civi\Osdi\RemoteSystemInterface
 *
 * @group headless
 */
class CRM_OSDI_ActionNetwork_MapperTest extends \PHPUnit\Framework\TestCase implements
    HeadlessInterface,
    HookInterface,
    TransactionalInterface {

  private static $createdEntities = [];

  /**
   * @var \Civi\Osdi\ActionNetwork\RemoteSystem
   */
  private $system;

  /**
   * @var \Civi\Osdi\ActionNetwork\Mapper\Example
   */
  private $mapper;

  public function setUpHeadless(): \Civi\Test\CiviEnvBuilder {
    return \Civi\Test::headless()->installMe(__DIR__)->apply();
  }

  public function setUp(): void {
    $this->system = CRM_OSDI_ActionNetwork_TestUtils::createRemoteSystem();
    $this->mapper = $this->createMapper($this->system);
    CRM_OSDI_FixtureHttpClient::resetHistory();
    parent::setUp();
  }

  public function tearDown(): void {
    $reset = $this->getCookieCutterOsdiPerson();
    parent::tearDown();
  }

  public static function tearDownAfterClass(): void {
    foreach (self::$createdEntities as $type => $ids) {
      foreach ($ids as $id) {
        civicrm_api4($type, 'delete', [
          'where' => [['id', '=', $id]],
          'checkPermissions' => FALSE,
        ]);
      }
    }

    parent::tearDownAfterClass();
  }

  private function createMapper(\Civi\Osdi\ActionNetwork\RemoteSystem $system) {
    return new Civi\Osdi\ActionNetwork\Mapper\Example($system);
  }

  public function makeBlankOsdiPerson(): \Civi\Osdi\ActionNetwork\Object\Person {
    return new \Civi\Osdi\ActionNetwork\Object\Person();
  }

  /**
   * @return \Civi\Osdi\ActionNetwork\Object\Person
   * @throws \Civi\Osdi\Exception\InvalidArgumentException
   */
  private function getCookieCutterOsdiPerson(): \Civi\Osdi\ActionNetwork\Object\Person {
    $unsavedNewPerson = $this->makeBlankOsdiPerson();
    $unsavedNewPerson->set('given_name', 'Cookie');
    $unsavedNewPerson->set('family_name', 'Cutter');
    $unsavedNewPerson->set('email_addresses', [['address' => 'cookie@yum.net']]);
    $unsavedNewPerson->set('phone_numbers', [['number' => '12023334444']]);
    $unsavedNewPerson->set('postal_addresses', [[
      'address_lines' => ['202 N Main St'],
      'locality' => 'Licking',
      'region' => 'MO',
      'postal_code' => '65542',
      'country' => 'US',
    ],
    ]);
    $unsavedNewPerson->set('languages_spoken', ['es']);
    return $this->system->save($unsavedNewPerson);
  }

  private function getCookieCutterCiviContact(): array {
    $createContact = Civi\Api4\Contact::create()->setValues(
      [
        'first_name' => 'Cookie',
        'last_name' => 'Cutter',
        'preferred_language:name' => 'es_MX',
      ]
    )->addChain('email', \Civi\Api4\Email::create()
      ->setValues(
        [
          'contact_id' => '$id',
          'email' => 'cookie@yum.net',
        ]
      )
    )->addChain('phone', \Civi\Api4\Phone::create()
      ->setValues(
        [
          'contact_id' => '$id',
          'phone' => '12023334444',
          'phone_type_id:name' => 'Mobile',
        ]
      )
    )->addChain('address', \Civi\Api4\Address::create()
      ->setValues(
        [
          'contact_id' => '$id',
          'street_address' => '123 Test St',
          'city' => 'Licking',
          'state_province_id:name' => 'Missouri',
          'postal_code' => 65542,
          'country_id:name' => 'US',
        ]
      )
    )->execute();
    $cid = $createContact->single()['id'];
    return Civi\Api4\Contact::get(0)
      ->addWhere('id', '=', $cid)
      ->addJoin('Address')->addJoin('Email')->addJoin('Phone')
      ->addSelect('*', 'address.*', 'address.state_province_id:name', 'address.country_id:name', 'email.*', 'phone.*')
      ->execute()
      ->single();
  }

  /**
   *
   * LOCAL ===> REMOTE
   *
   */
  public function testMapLocalContactToNewRemotePerson() {
    $civiContact = $this->getCookieCutterCiviContact();
    $this->assertEquals('Missouri', $civiContact['address.state_province_id:name']);
    $stateAbbreviation = 'MO';

    $result = $this->mapper->mapContactOntoRemotePerson($civiContact['id']);
    $this->assertEquals('Civi\Osdi\ActionNetwork\Object\Person', get_class($result));
    $this->assertEquals($civiContact['first_name'], $result->get('given_name'));
    $this->assertEquals($civiContact['last_name'], $result->get('family_name'));
    $this->assertEquals($civiContact['address.street_address'], $result->get('postal_addresses')[0]['address_lines'][0]);
    $this->assertEquals($civiContact['address.city'], $result->get('postal_addresses')[0]['locality']);
    $this->assertEquals($stateAbbreviation, $result->get('postal_addresses')[0]['region']);
    $this->assertEquals($civiContact['address.postal_code'], $result->get('postal_addresses')[0]['postal_code']);
    $this->assertEquals($civiContact['address.country_id:name'], $result->get('postal_addresses')[0]['country']);
    $this->assertEquals($civiContact['email.email'], $result->get('email_addresses')[0]['address']);
    $this->assertEquals($civiContact['phone.phone_numeric'], $result->get('phone_numbers')[0]['number']);
    $this->assertEquals(substr($civiContact['preferred_language'], 0, 2), $result->get('languages_spoken')[0]);
  }

  public function testMapLocalContactOntoExistingRemotePerson_ChangeName() {
    $existingRemotePerson = $this->getCookieCutterOsdiPerson();
    $civiContact = $this->getCookieCutterCiviContact();
    Civi\Api4\Contact::update(0)
      ->addWhere('id', '=', $civiContact['id'])
      ->setValues(['first_name' => 'DifferentFirst', 'last_name' => 'DifferentLast'])
      ->execute();

    $result = $this->mapper->mapContactOntoRemotePerson(
      $civiContact['id'],
      $existingRemotePerson
    );
    $this->assertEquals('Civi\Osdi\ActionNetwork\Object\Person', get_class($result));
    $this->assertEquals('DifferentFirst', $result->get('given_name'));
    $this->assertEquals('DifferentLast', $result->get('family_name'));
    $this->assertEquals($civiContact['email.email'], $result->get('email_addresses')[0]['address']);
  }

  public function testMapLocalContactOntoExistingRemotePerson_ChangePhone() {
    $existingRemotePerson = $this->getCookieCutterOsdiPerson();
    self::assertNotEquals('19098887777',
      $existingRemotePerson->get('phone_numbers')[0]['number']);
    $civiContact = $this->getCookieCutterCiviContact();
    Civi\Api4\Phone::update(0)
      ->addWhere('id', '=', $civiContact['phone.id'])
      ->addValue('phone', '19098887777')
      ->execute();

    $result = $this->mapper->mapContactOntoRemotePerson(
      $civiContact['id'],
      $existingRemotePerson
    );
    $this->assertEquals('Civi\Osdi\ActionNetwork\Object\Person', get_class($result));
    $this->assertEquals('19098887777', $result->get('phone_numbers')[0]['number']);
    $this->assertEquals($civiContact['first_name'], $result->get('given_name'));
    $this->assertEquals($civiContact['last_name'], $result->get('family_name'));
  }

  public function testMapLocalToRemote_EmailShouldBeUnsubscribed() {
    $existingRemotePerson = $this->getCookieCutterOsdiPerson();
    $civiContact = $this->getCookieCutterCiviContact();
    Civi\Api4\Contact::update(FALSE)
      ->addWhere('id', '=', $civiContact['id'])
      ->addValue('do_not_email', TRUE)
      ->execute();

    $result = $this->mapper->mapContactOntoRemotePerson(
      $civiContact['id'],
      $existingRemotePerson
    );
    $this->assertEquals('unsubscribed',
      $result->get('email_addresses')[0]['status']);
  }

  public function testMapLocalToRemote_PhoneShouldBeUnsubscribed_DoNotSms() {
    $existingRemotePerson = $this->getCookieCutterOsdiPerson();
    $civiContact = $this->getCookieCutterCiviContact();
    Civi\Api4\Contact::update(FALSE)
      ->addWhere('id', '=', $civiContact['id'])
      ->addValue('do_not_sms', TRUE)
      ->execute();

    $result = $this->mapper->mapContactOntoRemotePerson(
      $civiContact['id'],
      $existingRemotePerson
    );
    $this->assertEquals('unsubscribed',
      $result->get('phone_numbers')[0]['status']);
  }

  public function testMapLocalToRemote_PhoneShouldBeUnsubscribed_NoSmsNumber() {
    $existingRemotePerson = $this->getCookieCutterOsdiPerson();
    $civiContact = $this->getCookieCutterCiviContact();
    Civi\Api4\Phone::delete(FALSE)
      ->addWhere('contact_id', '=', $civiContact['id'])
      ->addWhere('phone_type_id:name', '=', 'Mobile')
      ->execute();

    $result = $this->mapper->mapContactOntoRemotePerson(
      $civiContact['id'],
      $existingRemotePerson
    );
    $this->assertEquals('unsubscribed',
      $result->get('phone_numbers')[0]['status']);
  }

  public function testMapLocalToRemote_EmailAndPhoneShouldBeUnsubscribed() {
    $existingRemotePerson = $this->getCookieCutterOsdiPerson();
    $civiContact = $this->getCookieCutterCiviContact();
    Civi\Api4\Contact::update(FALSE)
      ->addWhere('id', '=', $civiContact['id'])
      ->addValue('is_opt_out', TRUE)
      ->execute();

    $result = $this->mapper->mapContactOntoRemotePerson(
      $civiContact['id'],
      $existingRemotePerson
    );
    $this->assertEquals('unsubscribed',
      $result->get('email_addresses')[0]['status']);
    $this->assertEquals('unsubscribed',
      $result->get('phone_numbers')[0]['status']);
  }

  public function testMapLocalToRemote_EmailAndPhoneShouldBeSubscribed() {
    $existingRemotePerson = $this->getCookieCutterOsdiPerson();
    $civiContact = $this->getCookieCutterCiviContact();
    Civi\Api4\Contact::update(FALSE)
      ->addWhere('id', '=', $civiContact['id'])
      ->addValue('is_opt_out', FALSE)
      ->addValue('do_not_email', FALSE)
      ->addValue('do_not_sms', FALSE)
      ->addValue('do_not_phone', TRUE)
      ->execute();

    $result = $this->mapper->mapContactOntoRemotePerson(
      $civiContact['id'],
      $existingRemotePerson
    );
    $this->assertEquals('subscribed',
      $result->get('email_addresses')[0]['status']);
    $this->assertEquals('subscribed',
      $result->get('phone_numbers')[0]['status']);
  }

  /**
   *
   * REMOTE ===> LOCAL
   *
   */
  public function testMapRemotePersonToNewLocalContact() {
    $remotePerson = $this->getCookieCutterOsdiPerson();
    $this->assertEquals('MO', $remotePerson->get('postal_addresses')[0]['region']);
    $stateName = 'Missouri';

    $result = $this->mapper->mapRemotePersonOntoContact($remotePerson);
    $this->assertEquals('Civi\Api4\Action\Contact\Create', get_class($result));
    $cid = $result->execute()->single()['id'];
    $resultContact = Civi\Api4\Contact::get(0)
      ->addWhere('id', '=', $cid)
      ->addJoin('Address')->addJoin('Email')->addJoin('Phone')
      ->addSelect('*', 'address.*', 'address.state_province_id:name', 'address.country_id:name', 'email.*', 'phone.*')
      ->execute()
      ->single();
    $this->assertEquals($remotePerson->get('given_name'), $resultContact['first_name']);
    $this->assertEquals($remotePerson->get('family_name'), $resultContact['last_name']);
    $this->assertEquals($remotePerson->get('postal_addresses')[0]['address_lines'][0], $resultContact['address.street_address']);
    $this->assertEquals($remotePerson->get('postal_addresses')[0]['locality'], $resultContact['address.city']);
    $this->assertEquals($stateName, $resultContact['address.state_province_id:name']);
    $this->assertEquals($remotePerson->get('postal_addresses')[0]['postal_code'], $resultContact['address.postal_code']);
    $this->assertEquals($remotePerson->get('postal_addresses')[0]['country'], $resultContact['address.country_id:name']);
    $this->assertEquals($remotePerson->get('email_addresses')[0]['address'], $resultContact['email.email']);
    $this->assertEquals($remotePerson->get('phone_numbers')[0]['number'], $resultContact['phone.phone_numeric']);
    $this->assertEquals($remotePerson->get('languages_spoken')[0], substr($resultContact['preferred_language'], 0, 2));
  }

  public function testMapRemotePersonOntoExistingLocalContact_ChangeName() {
    $existingLocalContactId = $this->getCookieCutterCiviContact()['id'];
    $existingRemotePerson = $this->getCookieCutterOsdiPerson();
    $existingRemotePerson->set('given_name', 'DifferentFirst');
    $existingRemotePerson->set('family_name', 'DifferentLast');
    $alteredRemotePerson = $this->system->save($existingRemotePerson);

    $result = $this->mapper->mapRemotePersonOntoContact(
      $alteredRemotePerson,
      $existingLocalContactId
    );
    $this->assertEquals('Civi\Api4\Action\Contact\Update', get_class($result));
    $this->assertEquals('DifferentFirst', $result->getValue('first_name'));
    $this->assertEquals('DifferentLast', $result->getValue('last_name'));
    $this->assertEquals(
      $existingRemotePerson->get('email_addresses')[0]['address'],
      $result->getChain()['email'][2]['values']['email']
    );
  }

  public function testMapRemotePersonOntoExistingLocalContact_ChangePhone() {
    $existingLocalContactId = $this->getCookieCutterCiviContact()['id'];
    $existingRemotePerson = $this->getCookieCutterOsdiPerson();
    $existingRemotePerson->set('phone_numbers', [['number' => '19098887777']]);
    $alteredRemotePerson = $this->system->save($existingRemotePerson);

    $result = $this->mapper->mapRemotePersonOntoContact(
      $alteredRemotePerson,
      $existingLocalContactId
    );
    $this->assertEquals('19098887777', $result->getChain()['phone'][2]['values']['phone']);
    $this->assertEquals($existingRemotePerson->get('given_name'), $result->getValue('first_name'));
    $this->assertEquals($existingRemotePerson->get('family_name'), $result->getValue('last_name'));
  }

}
