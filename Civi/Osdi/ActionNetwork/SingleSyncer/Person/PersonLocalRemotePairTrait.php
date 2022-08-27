<?php

namespace Civi\Osdi\ActionNetwork\SingleSyncer\Person;

use Civi\Osdi\Exception\EmptyResultException;
use Civi\Osdi\Exception\InvalidArgumentException;
use Civi\Osdi\LocalObject\LocalObjectInterface;
use Civi\Osdi\LocalRemotePair;
use Civi\Osdi\PersonSyncState;
use Civi\Osdi\RemoteObjectInterface;
use Civi\Osdi\Result\Match;
use Civi\Osdi\Result\Sync;
use Civi\Osdi\Util;

trait PersonLocalRemotePairTrait {

  protected function fillLocalRemotePairFromSyncState(
    LocalRemotePair &$pair,
    PersonSyncState $syncState
  ): bool {
    $pair->setPersonSyncState($syncState);

    if (empty($syncState->getContactId()) || empty($syncState->getRemotePersonId())) {
      return FALSE;
    }

    $localPerson = $pair->getLocalObject();
    $localPersonClass = $pair->getLocalClass();
    $remotePerson = $pair->getRemoteObject();
    $remotePersonClass = $pair->getRemoteClass();

    if (!is_null($localPerson)) {
      Util::assertClass($localPerson, $localPersonClass);
    }
    if (!is_null($remotePerson)) {
      Util::assertClass($remotePerson, $remotePersonClass);
    }

    try {
      $localObject = $localPerson ??
        (new $localPersonClass($syncState->getContactId()))->load();
      $remoteObject = $remotePerson ??
        call_user_func(
          [$remotePersonClass, 'loadFromId'],
          $syncState->getRemotePersonId(), $this->getRemoteSystem());
    }
    catch (InvalidArgumentException | EmptyResultException $e) {
      $syncState->delete();
    }

    if (!is_null($localObject) && !is_null($remoteObject)) {
      $pair->setLocalObject($localObject)
        ->setRemoteObject($remoteObject)
        ->setIsError(FALSE)
        ->setMessage('fetched saved match');
      return TRUE;
    }

    return FALSE;
  }

  protected function fillLocalRemotePairFromNewfoundMatch(
    Match $matchResult,
    LocalRemotePair $pair
  ): LocalRemotePair {
    if (Match::ORIGIN_LOCAL === $matchResult->getOrigin()) {
      $localObject = $matchResult->getOriginObject();
      $remoteObject = $matchResult->getMatch();
    }
    else {
      $localObject = $matchResult->getMatch();
      $remoteObject = $matchResult->getOriginObject();
    }

    $syncState = new PersonSyncState();
    $syncState->setContactId($localObject->loadOnce()->getId());
    $syncState->setRemotePersonId($remoteObject->getId());
    $syncState->setSyncProfileId($this->syncProfileId);

    return $pair->setLocalObject($localObject)
      ->setRemoteObject($remoteObject)
      ->setIsError(FALSE)
      ->setMessage('found new match with existing object')
      ->setPersonSyncState($syncState)
      ->setMatchResult($matchResult);
  }

  protected function fillLocalRemotePairFromSyncResult(
    Sync $syncResult,
    LocalRemotePair $pair
  ): LocalRemotePair {
    $pair->setLocalObject($syncResult->getLocalObject())
      ->setRemoteObject($syncResult->getRemoteObject())
      ->setIsError($syncResult->isError())
      ->setMessage($syncResult->isError()
        ? 'error creating matching object' : 'created matching object')
      ->setPersonSyncState($syncResult->getState())
      ->setSyncResult($syncResult);
    return $pair;
  }

  public function toLocalRemotePair(
    LocalObjectInterface $localObject = NULL,
    RemoteObjectInterface $remoteObject = NULL
  ): LocalRemotePair {
    $pair = new LocalRemotePair($localObject, $remoteObject);
    $pair->setLocalClass($this->getLocalObjectClass());
    $pair->setRemoteClass($this->getRemoteObjectClass());
    return $pair;
  }

}