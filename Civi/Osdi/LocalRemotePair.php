<?php

namespace Civi\Osdi;

use Civi\Osdi\Exception\InvalidArgumentException;
use Civi\Osdi\Exception\InvalidOperationException;
use Civi\Osdi\LocalObject\LocalObjectInterface;
use Civi\Osdi\Result\Match;
use Civi\Osdi\Result\ResultStack;
use Civi\Osdi\Result\Sync;

class LocalRemotePair {

  const ORIGIN_LOCAL = 'local';
  const ORIGIN_REMOTE = 'remote';

  private ?LocalObjectInterface $localObject;
  private ?RemoteObjectInterface $remoteObject;
  private ?string $localClass = NULL;
  private ?string $remoteClass = NULL;
  private ResultStack $resultStack;

  private bool $isError;

  private ?string $message;
  private ?PersonSyncState $personSyncState;
  private ?Match $matchResult;
  private ?Sync $syncResult;

  private $origin;

  public function __construct(
      LocalObjectInterface $localObject = NULL,
      RemoteObjectInterface $remoteObject = NULL,
      bool $isError = FALSE,
      string $message = NULL,
      $personSyncState = NULL,
      Match $matchResult = NULL,
      Sync $syncResult = NULL) {
    $this->localObject = $localObject;
    $this->remoteObject = $remoteObject;
    $this->isError = $isError;
    $this->message = $message;
    $this->personSyncState = $personSyncState;
    $this->matchResult = $matchResult;
    $this->syncResult = $syncResult;
    $this->resultStack = new ResultStack();
  }

  public function getLocalObject(): ?LocalObjectInterface {
    return $this->localObject;
  }

  public function setLocalObject(?LocalObjectInterface $localObject): self {
    $this->localObject = $localObject;
    return $this;
  }

  public function getRemoteObject(): ?RemoteObjectInterface {
    return $this->remoteObject;
  }

  public function setRemoteObject(?RemoteObjectInterface $remoteObject): self {
    $this->remoteObject = $remoteObject;
    return $this;
  }

  public function getOrigin(bool $throwExceptionIfNotSet = FALSE): ?string {
    if ($throwExceptionIfNotSet && empty($this->origin)) {
      throw new InvalidOperationException('Origin retrieved without being set first');
    }
    return $this->origin;
  }

  public function isOriginLocal(): bool {
    return self::ORIGIN_LOCAL === $this->getOrigin(TRUE);
  }

  public function isOriginRemote(): bool {
    return self::ORIGIN_REMOTE === $this->getOrigin(TRUE);
  }

  public function setOrigin(string $origin): self {
    if (!in_array($origin, [self::ORIGIN_LOCAL, self::ORIGIN_REMOTE])) {
      throw new InvalidArgumentException('Invalid origin code: "%s"', $origin);
    }
    $this->origin = $origin;
    return $this;
  }

  public function getOriginObject() {
    if (empty($this->origin)) {
      throw new InvalidArgumentException('No origin code provided');
    }
    if (self::ORIGIN_LOCAL === $this->origin) {
      return $this->getLocalObject();
    }
    return $this->getRemoteObject();
  }

  public function getTargetObject() {
    if (empty($this->origin)) {
      throw new InvalidArgumentException('No origin code provided');
    }
    if (self::ORIGIN_REMOTE === $this->origin) {
      return $this->getLocalObject();
    }
    return $this->getRemoteObject();
  }

  /**
   * @param \Civi\Osdi\LocalObject\LocalObjectInterface|\Civi\Osdi\RemoteObjectInterface $object
   *
   * @return $this
   */
  public function setTargetObject($object): self {
    if ($this->isOriginLocal()) {
      return $this->setRemoteObject($object);
    }
    return $this->setLocalObject($object);
  }

  public function getResultStack(): ResultStack {
    return $this->resultStack;
  }

  public function isError(): bool {
    return $this->isError;
  }

  public function setIsError(bool $isError): self {
    $this->isError = $isError;
    return $this;
  }

  public function getMessage(): ?string {
    return $this->message;
  }

  public function setMessage(?string $message): self {
    $this->message = $message;
    return $this;
  }

  public function getPersonSyncState() {
    return $this->personSyncState;
  }

  public function setPersonSyncState($personSyncState) {
    $this->personSyncState = $personSyncState;
    return $this;
  }

  public function getMatchResult(): ?Match {
    return $this->matchResult;
  }

  public function setMatchResult(?Match $matchResult): self {
    $this->matchResult = $matchResult;
    return $this;
  }

  public function getSyncResult(): ?Sync {
    return $this->syncResult;
  }

  public function setSyncResult(?Sync $syncResult): self {
    $this->syncResult = $syncResult;
    return $this;
  }

  public function getLocalClass(): ?string {
    return $this->localClass;
  }

  public function setLocalClass(?string $localPersonClass): self {
    $this->localClass = $localPersonClass;
    return $this;
  }

  public function getRemoteClass(): ?string {
    return $this->remoteClass;
  }

  public function setRemoteClass(?string $className) {
    $this->remoteClass = $className;
    return $this;
  }

}
