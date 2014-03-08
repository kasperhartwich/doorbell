<?php
function play_sound($sound_file = 'friedland.mp3') {
	exec('omxplayer ' . dirname(__FILE__) . '/../sounds/' . $sound_file . '  > /dev/null &');
}