<?php
// =================================================================================================
//   StrategyBestTarget
// =================================================================================================
class StrategyBestTarget extends AStrategy
{
  const NB_TURNS_MAX = 50;
  const NB_TURNS_WAIT_MAX = 10;

  public function computeCommand($chip)
  {
    $bestTarget = $this->_computeBestTarget($chip);
    if (!isset($bestTarget) || $this->game->willCollide($chip, $bestTarget, StrategyBestTarget::NB_TURNS_WAIT_MAX)) return Game::WAIT_COMMAND;
    else {
      $nbTurnsBeforeCollision = $this->game->nbTurnsBeforeCollision($chip, $bestTarget, StrategyBestTarget::NB_TURNS_MAX);
      if (isset($nbTurnsBeforeCollision)) {
        $potentialLoss = $chip->getRadiusLoss($nbTurnsBeforeCollision);
        if ($potentialLoss > $bestTarget->getRadius()) return Game::WAIT_COMMAND;
      }
      else if ($chip->getRadiusLoss(1) >= $bestTarget->getRadius() && !$chip->isImmobile()) return Game::WAIT_COMMAND;
    }
    $command = $this->_computeCommandToTarget($chip, $bestTarget);
    return $command;
  }

  private function _computeBestTarget($chip)
  {
    $bestScore = -INF;
    $bestTarget = null;
    foreach ($this->game->getEntities() as $target) {
      if ($chip->getId() != $target->getId()) {
        $scoreEntity = $this->_computeScore($chip, $target);
        if ($scoreEntity > $bestScore) {
          $bestScore = $scoreEntity;
          $bestTarget = $target;
        }
      }
    }
    return $bestTarget;
  }

  private function _computeScore($chip, $target)
  {
    $radiusScoreWeight = 1;
    $reachableScoreWeight = 2;
    $radiusScore = $this->_computeRadiusScore($chip, $target) * $radiusScoreWeight;
    $reachableScore = $this->_computeReachableScore($chip, $target) * $reachableScoreWeight;
    return $radiusScore + $reachableScore;
  }

  private function _computeRadiusScore($chip, $target)
  {
    $bonusEnemy = 2;
    if (!$chip->isSameTeam($target) &&
    $target->getRadius() >= $chip->getRadius() - $chip->getRadiusLoss()) return -INF;
    $radiusScore = $target->getRadius();
    if (!$chip->isOpponent($target)) $radiusScore *= $bonusEnemy;
    return $radiusScore;
  }

  private function _computeReachableScore($chip, $target)
  {
    $commandToTarget = $this->_computeCommandToTarget($chip, $target);
    $chipNextTurn = $this->game->moveEntityCommand($chip, $commandToTarget);
    $nbTurnsBeforeCollision = $this->game->nbTurnsBeforeCollision($chipNextTurn, $target, StrategyBestTarget::NB_TURNS_MAX);
    if (!isset($nbTurnsBeforeCollision)) return -INF;
    else return -$nbTurnsBeforeCollision;
  }

  private function _computeCommandToTarget($chip, $target)
  {
    $futureChip = $this->game->moveEntity($chip, 1);
    $futureTarget = $this->game->moveEntity($target, 1);
    $direction = Vector::createVector($futureChip->getPosition(), $futureTarget->getPosition());
    $command = clone $chip->getPosition();
    $command->translate($direction);
    return $command;
  }
}
?>
