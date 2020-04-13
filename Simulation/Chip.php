<?php
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
?>
