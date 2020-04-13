<?php
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
?>
