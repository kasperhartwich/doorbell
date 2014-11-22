<?php
use PhpGpio\Gpio;

class Doorbell {

    private $gpio;
    private $pin;
    private $db;
    private $ipcamera;
    private $buzzer;
    private $image;
    private $ringed_status = false;
    private $ringed_at = false;
    private $sms;

    function __construct($pin, $db = null) {
        $this->pin = $pin;
        $this->db = $db;
        $this->gpio = new GPIO();
        if (isset($GLOBALS['config']['buzzer'])) {
            $this->buzzer = new GPIO();
            $this->buzzer->setup((int)$GLOBALS['config']['buzzer']['pin'], "out");
        }
        $this->gpio->setup($pin, "in");
        if ($GLOBALS['config']['sms']['enabled']) {
            $this->sms = new CPSMS($GLOBALS['config']['sms']['username'], $GLOBALS['config']['sms']['password']);
        }
        if ($GLOBALS['config']['ipcamera']) {
            $this->ipcamera = new IPCamera($GLOBALS['config']['ipcamera']['ip'], $GLOBALS['config']['ipcamera']['port'], $GLOBALS['config']['ipcamera']['username'], $GLOBALS['config']['ipcamera']['password']);
        }
    }

    function loop() {
        echo "Loop started.\n";
        while(true) {
            usleep(10000);
            $this->ringed_status = (int)$this->gpio->input($this->pin);
            if ($this->ringed_status && !$this->ringed_at) {
                $this->ring();
                $this->ringed_at = microtime(true);
            }
            if ($this->ringed_at && !$this->ringed_status) {
                $this->ringed();
                $this->ringed_at = false;
            }
        }
    }

    function ring() {

        //Buzzer
        if (isset($GLOBALS['config']['buzzer'])) {
            $this->buzzer->output((int)$GLOBALS['config']['buzzer']['pin'], 1);
        }

        //Play soundfile
        if (isset($GLOBALS['config']['soundfile'])) { $this->playSound($GLOBALS['config']['soundfile']); }

        $this->image = $this->saveWebcam();

        //SMS - TODO: Only sms if it's >1-2 min since last ring.
        if (isset($GLOBALS['config']['sms']['enabled']) && $GLOBALS['config']['sms']['enabled'] && isset($GLOBALS['config']['sms']['recipients'])) {
            foreach ($GLOBALS['config']['sms']['recipients'] as $recipient) {
                $this->sms->send("Ring ring! Se " . $GLOBALS['config']['host'] . '/webcam/' . $this->image, $recipient, $sender = $GLOBALS['config']['sms']['sender'] ?: false);
                echo "SMS sendt til " . $recipient . "\n";
            }
        }
    }

    function ringed() {
        //Buzzer
        if (isset($GLOBALS['config']['buzzer'])) {
            $this->buzzer->output((int)$GLOBALS['config']['buzzer']['pin'], 0);
        }

        $ringtime = microtime(true) - $this->ringed_at;
        echo "Det ringede i " . $ringtime . " d. " . date('c', $this->ringed_at) ."\n";

        $this->logDatabase($this->ringed_at, $ringtime, $this->image);
    }

    function logDatabase($ringed_at, $ringtime, $image = null) {
	try {
            $stmt = $this->db->prepare("INSERT INTO rings (ringed_at, ringtime, image) VALUES (:ringed_at, :ringtime, :image)");
            $stmt->bindParam(':ringed_at', date('c', $ringed_at));
            $stmt->bindParam(':ringtime', $ringtime);
            $stmt->bindParam(':image', $image);
            $stmt->execute();
        } catch (Exception $e) {
            echo 'Database error: ' . $e->getMessage. "\n";
        }
    }

    function test() {
        sleep(2);
        $this->ring();
        usleep(100);
        $this->ringed();
    }

    function saveWebcam() {
        $filename = uniqid() . '.jpg';
        try {
            $image = $this->ipcamera->snapshot();
        } catch (Exception $e) {
            echo 'Error getting image from webcam: ',  $e->getMessage(), "\n";
            echo 'image dump:';
            var_dump($image);
        }
        file_put_contents(__DIR__ . '/../public/webcam/' . $filename, $image);
        return $filename;
    }

    function playSound($sound_file = 'friedland.mp3') {
        exec('omxplayer ' . __DIR__ . '/../sounds/' . $sound_file . '  > /dev/null &');
    }

    function __destruct() {
        $this->gpio->unexportAll();
        $this->db = null;
    }
}
