<?php

class MachineDataCommand extends CConsoleCommand {
    private $oldVersionFormatMACs = array(
        '1','2','3','4','5','6','7','8','9','10','11','12','13'
    );

    public function actionCheck() {
        //Yii::import('application.modules.smto.models.*');

        $machines = Machine::model()->real_records()->cache(60)->findAll();
        //$cnt = count($machines);echo "$cnt".PHP_EOL;die;

        $dir = realpath(dirname(__FILE__ ) . '/../');
        //echo "$dir".PHP_EOL;die;
	
    	shuffle($machines);
	
	    $i = 1;
        foreach($machines as $machineAR) {
           if (empty($machineAR->mac)) {
               continue;
           }
//TODO
//            // Check max running import processes
//    	    $check = "ps ax | grep -v grep | grep -i 'MachineData import' | grep -v grep ";
//            //echo "$check" . PHP_EOL;
//
//            exec($check, $output);
//
//            $c = count($output);
//
//            if ( $c > 2 ) {
//                echo 'Running machines is too match!' . PHP_EOL;
//                return false;
//            }

            //Check is process import with $mac running?
            $check = "ps ax | grep -v grep | grep -i 'MachineData import --mac=" . $machineAR->mac . "'";
            //echo "$check" . PHP_EOL;
            $output = array();
            exec($check, $output);

	        $c = count($output);
            if ($c > 0) {
                echo "Machine with MAC {$machineAR->mac} is all ready processing..." . PHP_EOL;
                continue;
            } 
            

            $cmd = array();

            $cmd[] = "$dir/yiic";
            $cmd[] = "MachineData import";
            $cmd[] = "--mac=" . $machineAR->mac;
            $cmd[] = "--maxProcessDataFiles=" . 200;
            $cmd[] = "> /dev/null 2>/dev/null &";
            $cmd = implode(' ', $cmd);

            echo "$i) $cmd" . PHP_EOL;

            exec($cmd);
            $i++;
        }
    }

    public function actionImport($mac, $maxProcessDataFiles = 10) {

        $machineAR = Machine::getRecByMAC($mac);

        if (!$machineAR) {
            Yii::log("Machine with MAC: $mac is unknown!", 'warning', __METHOD__);
            Yii::app()->end();
        }

        $versionFormat = '2.0';
        if ( in_array($mac, $this->oldVersionFormatMACs) ) {
            $versionFormat = '1.0';
        }

        $import = new MachineDataImport($versionFormat, $mac);
        $dir = $machineAR->getMachineDataPath();

        //$output = Helpers::scandir($dir, $exp="/^cr.*$/i");
        //$output = Helpers::scandirFast($dir,"", true, 2);
        //echo print_r($output, true) . PHP_EOL; die();

        $import->run($dir, $maxProcessDataFiles);
    }
}
