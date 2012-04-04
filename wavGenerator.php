  <?php
  
    
    /**
     * GENERAL FUNCTIONS
     */
    function findValues($byte1, $byte2){
      $byte1 = hexdec(bin2hex($byte1));                        
      $byte2 = hexdec(bin2hex($byte2));                        
      return ($byte1 + ($byte2*256));
    }
    
    /**
     * Great function slightly modified as posted by Minux at
     * http://forums.clantemplates.com/showthread.php?t=133805
     */
    function html2rgb($input) {
      $input=($input[0]=="#")?substr($input, 1,6):substr($input, 0,6);
      return array(
       hexdec( substr($input, 0, 2) ),
       hexdec( substr($input, 2, 2) ),
       hexdec( substr($input, 4, 2) )
      );
    }
    /* coverts an mp3 into a wav image
     * @var string - filename of the file to be converted
     */
    function mp3toWavForm($fname,$width=500,$height=50,$foreground='#E2E2FF',$background='#000000',$detail=3)
    {
       $fileInfo = new SplFileInfo($fname);
       $finalFilename = preg_split('/\./', $fileInfo->getFilename());
       $finalFilename = md5($finalFilename[0]).'.png';
       
      /**
       * PROCESS THE FILE
       */
    
      // temporary file name
      $tmpname = substr(md5(time()), 0, 10);
      
      // copy from temp upload directory to current
      copy($fname, "{$tmpname}_o.mp3");
      
      /**
       * convert mp3 to wav using lame decoder
       * First, resample the original mp3 using as mono (-m m), 16 bit (-b 16), and 8 KHz (--resample 8)
       * Secondly, convert that resampled mp3 into a wav
       * We don't necessarily need high quality audio to produce a waveform, doing this process reduces the WAV
       * to it's simplest form and makes processing significantly faster
       */
      exec("lame.exe {$tmpname}_o.mp3 -f -m m -b 16 --resample 8 {$tmpname}.mp3 && lame --decode {$tmpname}.mp3 {$tmpname}.wav");
      
      // delete temporary files
      @unlink("{$tmpname}_o.mp3");
      @unlink("{$tmpname}.mp3");
      
      $filename = "{$tmpname}.wav";
      
      /**
       * Below as posted by "zvoneM" on
       * http://forums.devshed.com/php-development-5/reading-16-bit-wav-file-318740.html
       * as findValues() defined above
       * Translated from Croation to English - July 11, 2011
       */
      $handle = fopen ($filename, "r");
      //dohvacanje zaglavlja wav datoteke
      $heading[] = fread ($handle, 4);
      $heading[] = bin2hex(fread ($handle, 4));
      $heading[] = fread ($handle, 4);
      $heading[] = fread ($handle, 4);
      $heading[] = bin2hex(fread ($handle, 4));
      $heading[] = bin2hex(fread ($handle, 2));
      $heading[] = bin2hex(fread ($handle, 2));
      $heading[] = bin2hex(fread ($handle, 4));
      $heading[] = bin2hex(fread ($handle, 4));
      $heading[] = bin2hex(fread ($handle, 2));
      $heading[] = bin2hex(fread ($handle, 2));
      $heading[] = fread ($handle, 4);
      $heading[] = bin2hex(fread ($handle, 4));
      
      //bitrate wav datoteke
      $peek = hexdec(substr($heading[10], 0, 2));
      $byte = $peek / 8;
      
      //provjera da li se radi o mono ili stereo wavu
      $channel = hexdec(substr($heading[6], 0, 2));
      
      if($channel == 2){
        $omjer = 40;
      }
      else{
        $omjer = 80;
      }
      
      while(!feof($handle)){
        $bytes = array();
        //get number of bytes depending on bitrate
        for ($i = 0; $i < $byte; $i++){
          $bytes[$i] = fgetc($handle);
        }
        switch($byte){
          //get value for 8-bit wav
          case 1:
              $data[] = findValues($bytes[0], $bytes[1]);
              break;
          //get value for 16-bit wav
          case 2:
            if(ord($bytes[1]) & 128){
              $temp = 0;
            }
            else{
              $temp = 128;
            }
            $temp = chr((ord($bytes[1]) & 127) + $temp);
            $data[]= floor(findValues($bytes[0], $temp) / 256);
            break;
        }
        //skip bytes for memory optimization
        fread ($handle, $omjer);
      }
      
      // close and cleanup
      fclose ($handle);
      unlink("{$tmpname}.wav");
      
      /**
       * Image generation
       */
    
     // header("Content-Type: image/png");
     
      // how much detail we want. Larger number means less detail
      // (basically, how many bytes/frames to skip processing)
      // the lower the number means longer processing time

      
      // get user vars from form

      
      // create original image width based on amount of detail
      $img = imagecreatetruecolor(sizeof($data) / $detail, $height);
      
      // fill background of image
      list($r, $g, $b) = html2rgb($background);
      imagefilledrectangle($img, 0, 0, sizeof($data) / $detail, $height, imagecolorallocate($img, $r, $g, $b));
      
      // generate background color
      list($r, $g, $b) = html2rgb($foreground);
        
      // loop through frames/bytes of wav data as genearted above
      for($d = 0; $d < sizeof($data); $d += $detail) {
        // relative value based on height of image being generated
        // data values can range between 0 and 255
        $v = (int) ($data[$d] / 255 * $height);
        // draw the line on the image using the $v value and centering it vertically on the canvas
        imageline($img, $d / $detail, 0 + ($height - $v), $d / $detail, $height - ($height - $v), imagecolorallocate($img, $r, $g, $b));
      }
       //create file
       $fp = fopen('images/'.$finalFilename, 'w');
       fclose($fp);
      // want it resized?
      if ($width) {
               
        // resample the image to the proportions defined in the form
        $rimg = imagecreatetruecolor($width, $height);
        imagecopyresampled($rimg, $img, 0, 0, 0, 0, $width, $height, sizeof($data) / $detail, $height);

        imagepng($rimg,'images/'.$finalFilename);
        imagedestroy($rimg);
      
      } else {
      
        // print out at it's raw width (size of $data / detail level)
        imagepng($img, 'images/'.$finalFilename);
        imagedestroy($img);
      
      }
      
      return 'images/'.$finalFilename;
       
    } 

	if(isset($_GET['filename']))
	{
		ini_set("max_execution_time", "60000");   
		echo 'analyzing file'.$_GET['filename'];
		mp3toWavForm($_GET['filename']);
		echo 'done!';
	}
?>

 