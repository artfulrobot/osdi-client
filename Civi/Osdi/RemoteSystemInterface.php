<?php

namespace Civi\Osdi;

use Civi\Osdi\ActionNetwork\RemoteFindResult;
use Jsor\HalClient\HalClientInterface;
use Jsor\HalClient\HalResource;

interface RemoteSystemInterface {

  public function findAll(string $objectType): RemoteFindResult;

  public function getClient(): HalClientInterface;

  public function delete(RemoteObjectInterface $osdiObject);

  public function getEntryPoint(): string;

  public function fetch(RemoteObjectInterface $osdiObject);

  public function find(string $objectType, array $criteria): RemoteFindResult;

  public function makeOsdiObject(
    string $type,
    ?HalResource $resource
  ): RemoteObjectInterface;

  public function save(RemoteObjectInterface $osdiObject): HalResource;

}
