<?php

// =================================================================================================
//   Point
// =================================================================================================
class Point
{
  public $x;
  public $y;

  public function __construct($x, $y)
  {
    $this->x = $x;
    $this->y = $y;
  }

  public function distance($point)
  {
    return sqrt(pow($this->x - $point->x, 2) + pow($this->y - $point->y, 2));
  }

  public function translate($direction)
  {
    $this->x += $direction->x;
    $this->y += $direction->y;
  }

  public function rotate($center, $angle)
  {
    $angle *= M_PI / 180;
    $xTmp = $this->x - $center->x;
    $yTmp = $this->y - $center->y;
    $this->x = $xTmp * cos($angle) + $yTmp * sin($angle) + $center->x;
    $this->y = -$xTmp * sin($angle) + $yTmp * cos($angle) + $center->y;
  }

  public function multiply($factor)
  {
    $this->x *= $factor;
    $this->y *= $factor;
  }

  public function __toString()
  {
    return "{$this->x} {$this->y}\n";
  }
}



// =================================================================================================
//   Vector
// =================================================================================================
class Vector extends Point
{
  public static function createVector($a, $b)
  {
    return new Vector($b->x - $a->x, $b->y - $a->y);
  }

  public function getNorm()
  {
    return $this->distance(new Point(0, 0));
  }

  public function setNorm($newNorm)
  {
    $this->multiply($newNorm / $this->getNorm());
  }

  public function dotProduct($vector)
  {
    return $this->x * $vector->x + $this->y * $vector->y;
  }

  public function sum($vector)
  {
    $this->x += $vector->x;
    $this->y += $vector->y;
  }

  public function getAngle($vector)
  {
    return acos($this->dotProduct($vector) / ($this->getNorm() * $vector->getNorm()));
  }
}



// =================================================================================================
//   Entity
// =================================================================================================
abstract class Entity
{
  protected $id;
  protected $radius;
  protected $position;
  protected $speedVector;
  protected $speed;

  public function __construct($id, $radius, $x, $y, $vx, $vy)
	{
		$this->id = $id;
		$this->radius = $radius;
		$this->position = new Point($x, $y);
    $this->speedVector = new Vector($vx, $vy);
    $this->speed = $this->speedVector->getNorm();
	}

  public function distance($entity)
  {
    return $this->position->distance($entity->position);
  }

  public function getSpeed()
  {
    return $this->speed;
  }

  public function areColliding($entity)
  {
    return  $this->distance($entity) < $this->radius + $entity->radius;
  }

  public function isBigger($entity)
  {
    return $this->radius > $entity->radius;
  }

  public function getId()
  {
    return $this->id;
  }
  public function getRadius()
  {
    return $this->radius;
  }
  public function getPosition()
  {
    return $this->position;
  }
  public function getSpeedVector()
  {
    return $this->speedVector;
  }

  public function isImmobile()
  {
    return $this->speed == 0;
  }

  public function move()
  {
    $this->position->translate($this->speedVector);
  }

  public function __clone(){
    $this->position = clone $this->position;
    $this->speedVector = clone $this->speedVector;
  }
}



// =================================================================================================
//   Chip
// =================================================================================================
class Chip extends Entity
{
	const ACCELERATION = 200/14;
	private $_player;

	public function __construct($id, $player, $radius, $x, $y, $vx, $vy)
	{
		parent::__construct($id, $radius, $x, $y, $vx, $vy);
		$this->_player = $player;
	}

	public function moveCommand($x, $y)
	{
		$accelerationVector = Vector::createVector($this->position, new Point($x, $y));
		$accelerationVector->setNorm(Chip::ACCELERATION);
		$this->speedVector->sum($accelerationVector);
		$this->speed = $this->speedVector->getNorm();
		$this->move();
	}

  public function isSameTeam($entity)
  {
    return $entity instanceof Chip && $this->_player == $entity->_player;
  }
  public function isOpponent($entity)
  {
    return $entity instanceof Chip && $this->_player != $entity->_player;
  }

  public function getRadiusLoss($nbTurns=1)
  {
    if($nbTurns <= 0) return 0;
    $loss = $this->radius;
    for ($i = 0; $i < $nbTurns ; $i++) {
      $loss /= 15;
    }
    return $loss;
  }

  public function getPlayer()
  {
    return $this->_player;
  }
}



// =================================================================================================
//   Droplet
// =================================================================================================
class Droplet extends Entity
{

}



// =================================================================================================
//   Game
// =================================================================================================
class Game
{
  const WIDTH = 800;
  const HEIGHT = 515;
  const WAIT_COMMAND = "WAIT\n";

  private $_entities;
  private $_chips;

  public function __construct()
  {
    $this->reset();
  }

  public function addEntity($id, $player, $radius, $x, $y, $vx, $vy)
  {
    if ($player == -1) {
      $entity = new Droplet($id, $radius, $x, $y, $vx, $vy);
    }
    else {
      $entity = new Chip($id, $player, $radius, $x, $y, $vx, $vy);
      $this->_chips[$player][$id] = $entity;
    }
    $this->_entities[$id] = $entity;
  }

  public function moveEntity($entity, $nbTurns=1)
  {
    $newEntity = clone $entity;
    if (!$newEntity->isImmobile()) {
      for ($i = 0; $i < $nbTurns; $i++) {
        $newEntity->move();
        $this->_bounce($newEntity);
      }
    }
    return $newEntity;
  }

  public function moveEntityCommand($entity, $command)
  {
    if ($command == Game::WAIT_COMMAND) return $this->moveEntity($entity, 1);
    else {
      $newEntity = clone $entity;
      $newEntity->moveCommand($command->x, $command->y);
      $this->_bounce($newEntity);
      return $newEntity;
    }
  }

  public function willCollide($entity1, $entity2, $nbTurns=1)
  {
    $nbTurnsBeforeCollision = $this->nbTurnsBeforeCollision($entity1, $entity2, $nbTurns);
    if (!isset($nbTurnsBeforeCollision)) return false;
    else return $nbTurnsBeforeCollision <= $nbTurns;
  }

  public function nbTurnsBeforeCollision($entity1, $entity2, $nbTurnsMax=100)
  {
    $nbTurns = 0;
    while (!$entity1->areColliding($entity2)) {
      if ($nbTurns > $nbTurnsMax) return null;
      $entity1 = $this->moveEntity($entity1);
      $entity2 = $this->moveEntity($entity2);
      $nbTurns++;
    }
    return $nbTurns;
  }

  public function getPlayerChips($player)
  {
    if (isset($this->_chips[$player])) return $this->_chips[$player];
    else return array();
  }

  public function reset(){
    $this->_entities = array();
    $this->_chips = array();
  }

  public function getEntities()
  {
    return $this->_entities;
  }

  private function _bounce($entity)
  {
    $position = $entity->getPosition();
    $radius = $entity->getRadius();
    $crossLeftBound = $this->_crossLeftBound($entity);
    $crossRightBound = $this->_crossRightBound($entity);
    $crossTopBound = $this->_crossTopBound($entity);
    $crossBottomBound = $this->_crossBottomBound($entity);
    if ($crossLeftBound) $position->x = $radius - $position->x ;
    else if ($crossRightBound) $position->x -= 2 * Game::WIDTH + 1 + $radius;
    if ($crossTopBound) $position->y = $radius - $position->y;
    else if ($crossBottomBound) $position->y -= 2 * Game::HEIGHT + 1 + $radius;
    if ($crossLeftBound || $crossRightBound) $entity->getSpeedVector()->x *= -1;
    else if ($crossTopBound || $crossBottomBound) $entity->getSpeedVector()->y *= -1;
  }

  private function _crossTopBound($entity){
    $yMin = $entity->getRadius();
    return $entity->getPosition()->y < $yMin;
  }

  private function _crossRightBound($entity){
    $xMax = Game::WIDTH - $entity->getRadius();
    return $entity->getPosition()->x >= $xMax;
  }

  private function _crossBottomBound($entity){
    $yMax = Game::HEIGHT - $entity->getRadius();
    return $entity->getPosition()->y >= $yMax;
  }

  private function _crossLeftBound($entity){
    $xMin = $entity->getRadius();
    return $entity->getPosition()->x < $xMin;
  }
}



// =================================================================================================
//   IStrategy
// =================================================================================================
interface IStrategy
{
	public function computeCommand($chip);
}



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



 // To debug (equivalent to var_dump): error_log(var_export($var, true));
$game = new Game();
$strategy = new StrategySwitcher($game);

fscanf(STDIN, "%d", $playerId);

while (TRUE)
{
    $game->reset();
    fscanf(STDIN, "%d", $playerChipCount);
    fscanf(STDIN, "%d", $entityCount);
    for ($i = 0; $i < $entityCount; $i++) {
        fscanf(STDIN, "%d %d %f %f %f %f %f", $id, $player, $radius, $x, $y, $vx, $vy);
        $game->addEntity($id, $player, $radius, $x, $y, $vx, $vy);
    }
    foreach ($game->getPlayerChips($playerId) as $chip) {
      echo($strategy->computeCommand($chip));
    }
}

?>