<?php
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
?>
