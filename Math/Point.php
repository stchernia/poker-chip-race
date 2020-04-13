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
?>
