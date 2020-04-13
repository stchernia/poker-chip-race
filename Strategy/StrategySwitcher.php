<?php
// =================================================================================================
//   StrategySwitcher
// =================================================================================================
class StrategySwitcher extends AStrategy
{
  private $_strategyWait;
  private $_strategyBestTarget;
  private $_strategyEscape;
  private $_currentStrategy;

  public function __construct($game)
  {
    parent::__construct($game);
    $this->_strategyWait = new StrategyWait($game);
    $this->_strategyBestTarget = new StrategyBestTarget($game);
    $this->_strategyEscape = new StrategyEscape($game);
    $this->_switchToBestTarget();
  }

  public function computeCommand($chip)
  {
    if ($this->_isGameWon($chip)) $this->_switchToWaitStrategy();
    else if (!$this->_strategyEscape->isSafe($chip)) $this->_switchToEscapeStrategy();
    else $this->_switchToBestTarget();
    return $this->_currentStrategy->computeCommand($chip);
  }

  private function _isGameWon($chip){
    $fatestChip = $chip;
    foreach ($this->game->getPlayerChips($chip->getPlayer()) as $playerChip) {
      if ($playerChip->getRadius() > $fatestChip->getRadius()) $fatestChip = $playerChip;
    }
    $sumOtherEntities = 0;
    foreach ($this->game->getEntities() as $entity) {
      if ($entity != $fatestChip) $sumOtherEntities += $entity->getRadius();
    }
    return $fatestChip->getRadius() > $sumOtherEntities;
  }

  private function _switchToEscapeStrategy()
  {
    $this->_currentStrategy = $this->_strategyEscape;
  }

  private function _switchToWaitStrategy()
  {
    $this->_currentStrategy = $this->_strategyWait;
  }
  
  private function _switchToBestTarget()
  {
    $this->_currentStrategy = $this->_strategyBestTarget;
  }
}
?>
