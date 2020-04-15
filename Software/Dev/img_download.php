<?php

$url = 'https://gpnl.innogamescdn.com/images/game/flags/map/flag';

for ($x = 0; $x < 75; ++$x) {
  // Function to write image into file
  file_put_contents("flag$x.png", file_get_contents($url.$x.'.png'));
}
