<?php
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
