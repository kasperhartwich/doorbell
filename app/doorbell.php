<?php
use PhpGpio\Gpio;

class Doorbell {

    private $gpio;
    private $pin;
    private $db;
    private $ipcamera;
    private $image;
    private $ringed_status = false;
    private $ringed_at = false;
    private $sms;

    function __construct($pin, $db = null) {
        $this->pin = $pin;
        $this->db = $db;
        $this->gpio = new GPIO();
        $this->gpio->setup($pin, "in");
        if ($GLOBALS['config']['sms']['enabled']) {
            $this->sms = new CPSMS($GLOBALS['config']['sms']['username'], $GLOBALS['config']['sms']['password']);
        }
        if ($GLOBALS['config']['ipcamera']) {
            $this->ipcamera = new IPCamera($GLOBALS['config']['ipcamera']['ip'], $GLOBALS['config']['ipcamera']['port'], $GLOBALS['config']['ipcamera']['username'], $GLOBALS['config']['ipcamera']['password']);
        }
    }

    function loop() {
        echo "Reading input\n";
        file_put_contents('/tmp/doorbell-daemon', date('c'));
        while(true) {
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
        //Play soundfile
        if ($GLOBALS['config']['soundfile']) { $this->playSound($GLOBALS['config']['soundfile']); }

        $this->image = $this->saveWebcam();

        //SMS - TODO: Only sms if it's >1-2 min since last ring.
        if ($GLOBALS['config']['sms']['enabled']) {
            foreach ($GLOBALS['config']['sms']['recipients'] as $recipient) {
                $this->sms->send("Ring ring! Se " . $GLOBALS['config']['host'] . '/webcam/' . $this->image, $recipient, $sender = $GLOBALS['config']['sms']['sender'] ?: false);
                echo "SMS sendt til " . $recipient . "\n";
            }
        }
    }

    function ringed() {
        $ringtime = microtime(true) - $this->ringed_at;
        echo "Det ringede i " . $ringtime . " d. " . date('c', $this->ringed_at) ."\n";

        //Save to database
        $stmt = $this->db->prepare("INSERT INTO rings (ringed_at, ringtime, image) VALUES (:ringed_at, :ringtime, :image)");
        $stmt->bindParam(':ringed_at', date('c', $this->ringed_at));
        $stmt->bindParam(':ringtime', $ringtime);
        $stmt->bindParam(':image', $this->image);
        $stmt->execute();
    }

    function test() {
        sleep(2);
        $this->ring();
        usleep(100);
        $this->ringed();
    }

    function saveWebcam() {
        $filename = uniqid() . '.jpg';
        $image = $this->ipcamera->snapshot();
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