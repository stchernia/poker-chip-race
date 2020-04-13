<?php
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
?>
