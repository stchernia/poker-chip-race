<?php
// =================================================================================================
//   StrategyWait
// =================================================================================================
class StrategyWait extends AStrategy
{
  public function computeCommand($chip)
  {
    return Game::WAIT_COMMAND;
  }
}
?>
