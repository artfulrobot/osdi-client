<?php

namespace Civi\Osdi\Result;

use Civi\Osdi\ResultInterface;

class ResultStack extends \ArrayIterator implements \Iterator {

  /**
   * @var \Civi\Osdi\ResultInterface[]
   */
  private array $lastByType = [];

  /**
   * @var \Civi\Osdi\ResultInterface[]
   */
  private array $stack = [];

  private int $position = 0;

  public function __construct(ResultInterface $firstItem = NULL) {
    if ($firstItem) {
      $this->push($firstItem);
    }
    parent::__construct($this->stack);
  }

  public function getLastOfType(string $type) {
    return $this->lastByType[$type] ?? NULL;
  }

  public function last(): ?ResultInterface {
    return $this->stack[array_key_last($this->stack)] ?? NULL;
  }

  public function lastIsError(): bool {
    if (is_null($last = $this->last())) {
      return FALSE;
    }
    return $last->isError();
  }

  public function push(ResultInterface $item) {
    $this->stack[] = $item;
    $this->lastByType[$item->getType() ?? 'NULL'] = $item;
  }

  /**
   * Iterator implementation
   */
  public function rewind(): void {
    $this->position = array_key_last($this->stack);
  }

  /**
   * Iterator implementation
   */
  public function current() {
    return $this->stack[$this->position];
  }

  /**
   * Iterator implementation
   */
  public function key(): int {
    return $this->position;
  }

  /**
   * Iterator implementation
   */
  public function next(): void {
    --$this->position;
  }

  /**
   * Iterator implementation
   */
  public function valid(): bool {
    return isset($this->stack[$this->position]);
  }

}