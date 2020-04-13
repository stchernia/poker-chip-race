<?php
// =================================================================================================
//   AStrategy
// =================================================================================================
abstract class AStrategy implements IStrategy
{
  protected $game;

  public function __construct($game)
  {
    $this->game = $game;
  }
  abstract public function computeCommand($chip);
}
?>
