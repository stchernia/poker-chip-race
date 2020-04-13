<?php
// =================================================================================================
//   StrategyEscape
// =================================================================================================
class StrategyEscape extends AStrategy
{
  const NB_SAFE_TURNS = 8;
  const NB_SPOKES = 18;
  const SPOKE_ANGLE = 360 / StrategyEscape::NB_SPOKES;

  public function computeCommand($chip)
  {
    return $this->_computeSafestCommand($chip);
  }

  public function isSafe($chip)
  {
    foreach ($this->game->getEntities() as $entity) {
       if ($this->_isThreat($chip, $entity)) return false;
    }
    return true;
  }

  private function _computeSafestCommand($chip)
  {
    $safestCommand = Game::WAIT_COMMAND;
    $safestScore = $this->_computeCommandScore($chip, $safestCommand);
    $targetPosition = clone $chip->getPosition();
    $targetPosition->translate(new Vector(0, 10));
    $center = $chip->getPosition();
    for ($i = 1; $i < StrategyEscape::NB_SPOKES; $i++) {
      $targetPosition->rotate($center, StrategyEscape::SPOKE_ANGLE);
      $command = $this->_computeCommandToPosition($chip, $targetPosition);
      $score = $this->_computeCommandScore($chip, $command);
      if ($score > $safestScore) {
        $safestScore = $score;
        $safestCommand = $command;
      }
    }
    return $safestCommand;
  }

  private function _computeCommandScore($chip, $command)
  {
    $chipNextTurn = $this->game->moveEntityCommand($chip, $command);
    $score = $this->_computeSafety($chipNextTurn);
    if ($command == Game::WAIT_COMMAND) $score += 1;
    else {
      $direction = Vector::createVector($chip->getPosition(), $command);
      $score -= $chip->getSpeedVector()->getAngle($direction) / M_PI;
      foreach ($this->game->getEntities() as $entity) {
         if ($chip->isOpponent($entity)) {
           $directionOpponent = Vector::createVector($chip->getPosition(), $entity->getPosition());
           $angleOpponent = $direction->getAngle($directionOpponent);
           if ($angleOpponent > M_PI - 0.1) $score -= 1;
         }
      }
    }
    return $score;
  }

  private function _computeSafety($chip)
  {
    $safetyScore = 0;
    for ($i = 0; $i < StrategyEscape::NB_SAFE_TURNS; $i++) {
      foreach ($this->game->getEntities() as $entity) {
         if ($this->_isThreat($chip, $entity)) {
           $nbTurnsBeforeCollision = $this->game->nbTurnsBeforeCollision($chip, $entity, StrategyEscape::NB_SAFE_TURNS);
           if (isset($nbTurnsBeforeCollision)) $safetyScore -= $nbTurnsBeforeCollision;
         }
      }
      $chip = $this->game->moveEntity($chip);
    }
    return $safetyScore;
  }

  private function _isThreat($chip, $entity)
  {
    if ($chip->isSameTeam($entity) || $chip->isBigger($entity)) return false;
    return $this->game->willCollide($chip, $entity, StrategyEscape::NB_SAFE_TURNS);
  }

  private function _computeCommandToPosition($chip, $position)
  {
    $nextPosition = $this->game->moveEntity($chip)->getPosition();
    $direction = Vector::createVector($nextPosition, $position);
    $command = clone $chip->getPosition();
    $command->translate($direction);
    return $command;
  }
}
?>
