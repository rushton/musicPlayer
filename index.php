<!DOCTYPE html>
<html>
<head>
    <style>
        body
        {
            background-color:#ddd
            font: normal 62.5% "Trebuchet MS", Verdana, Helvetica, Arial,sans-serif;
			padding:0;
			margin:0;
        }
        audio
        {
            width:100%;
        }
        table
        {
            border-collapse:collapse;
            width:100%;
        }
		
        #library th
        {
            text-align:left;     
            background-color:#fff;
            
        }
        
        #library #tableHeadings
        {
            width:100%;
        }
        #library .songRow:nth-child(2n)
        {
            background-color:rgba(0,0,255,0.05);
        }
        .songRow:hover
        {
            background-color:rgba(0,0,255,0.15);
            cursor:pointer;
        }
		
		.songRow:nth-child(2n):hover
		{
			background-color:rgba(0,0,255,0.15);
		}
		
		
        #library
        {
            height:500px;
            overflow:auto;
            background-color:#eee; 
            position:relative;
        }
        #playlist
        {
            height:300px;
            border: 1px solid #000;
            overflow:auto;
            
        }
        #playlists
        {
            float:left;
            width:200px;
            height:300px;
            border:1px solid #000;
            overflow:auto;
            
        }
        
        .playing
        {
            background-color:#FEF1B5;
        }
        
        .selectedPlaylist
        {
            background-color:#FEF1B5;
        }
        
        .played
        {
            background-color:#fff;
            opacity: 0.5;
        }          
        .button
        {
            font-size:10px;
            display:block;
            width:100px;
            height:25px;
            text-decoration:none;
            border:1px solid #000;
            background-color:rgba(255,0,0,.4);
            text-align: center;
            line-height: 25px;
        }     
        
        #playlists a
        {
            display:block;
            text-decoration:none;
            font-size:13px;
            width:100%;
            color:#000;
        }  
        #playlists a:hover
        {
            background-color:#f00;
        } 
        
		.player
		{
		
		}
			#gutter
			{
				width:500px; 
				height:50px;
				padding:0;
				margin:0;
				background-color:#000;
				display:inline-block;  
				position: relative;
				
				top:-50px;
			} 
				#progress
				{
					height:50px;
					width:0%;
					background-color:rgba(255,0,0,0.3); 
				}   
			#playToggle
			{
				width:50px;
				height:50px;
				background-image: url('playpause.png');
				position: relative;
				top:-50px;
				display:inline-block;
			}  
			#timeleft
			{
				display:inline-block;
				font-size:20px;
				line-height: 48px;
				vertical-align: top;
				position: relative;
				top:35px;
				left:-505px;
			}
			#volumeKnob
			{
				background: url('volumeknob.png');
				width:100px;
				display:inline-block;
				height:100px;
				-webkit-transform: rotate(585deg); 
				position: relative;
			}
		
		#searchBox
		{
			width:500px;
		}
		
		div.player
		{
			background-color:#fff;
		}
		
		.relative
		{
			position:relative;
		}
                   
    </style>
</head>
<body>
<div class="player">
    <div class="relative" id="playToggle"></div>
    <div class="relative" id="gutter">
        <div id="progress"></div>
        <div id="loaded"></div>
    </div>
    <div class="relative" id="timeleft"></div>
	<div class="relative" id="volumeKnob"></div>
</div>   
<a href="javascript://" class="button" id="clearPlaylist">Clear Playlist</a>
<a href="javascript://" class="button" id="addPlaylist">Add Playlist</a>

<?php



class MusicLibrary
{
    
    private $id3;
    
    public function __construct()
    {
        include('getid3/getid3.php');
        include('wavGenerator.php');
        $this->id3 = new getID3();
    }
    
    /* gets the image name of the corresponding mp3 file
     * @var string - name of the mp3 file 
     * @return string - filename
     */
    private static function getImageName($filename)
    {
        $name = preg_split('/\./', $filename);
        $name = md5($name[0]);
        return $name.'.png';
    }
    
    /*
     * @var string - directory of the music library
     * @return array - mp3 files  and information about them
     */
     public function buildFiles($dir)
     {
        $fileIterator = new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)), '/\.mp3$/');
        
        $files = array();
        
        foreach($fileIterator as $index => $file)
        {

            $files[$index]['filePath']  = htmlspecialchars($file->getPath().'/'.$file->getFilename());
            $files[$index]['fileName']  = htmlspecialchars($file->getFilename());
            $files[$index]['info']      = $this->id3->analyze($file->getPath().'/'.$file->getFilename());
			unset($files[$index]['info']['comments']);
			unset($files[$index]['info']['id3v2']['APIC']);
			unset($files[$index]['info']['mpeg']);
            if(!file_exists('images/'.self::getImageName($file->getFilename())))
            {          
                $files[$index]['imagePath'] = mp3toWavForm($file->getPath().'/'.$file->getFilename());
            }
            else
                $files[$index]['imagePath'] = 'images/'.self::getImageName($file->getFilename());
        }   

		
        return $files;
     }
	 
	 /*
	  *builds playlist in myPlaylist.txt for di.fm stations
	  */
	 public function buildRadioPlaylists()
	 {
		$stationsJSON = file_get_contents('http://listen.di.fm/public3');
		$stations = json_decode($stationsJSON,1);
		$songs = array();
		foreach($stations as $station)
		{
			$pls = file_get_contents($station['playlist'])."\n\n";
			preg_match('/http:.*/m', $pls, $matches);
			$song = array( 'title'    => $station['name'],
						   'duration' => 'Forever',
						   'artist'   => 'di.fm',
						   'album'    => '',
						   'path'     => $matches[0],
						   'imagepath'=> '');
			array_push($songs, $song);
		}
		
		$playlistMaster = json_decode(file_get_contents('myPlaylist.txt'),true);
		
		$playlistMaster['di.fm']['Songs'] = $songs;

                                            
        $playlistMaster = json_encode($playlistMaster);
        $file = fopen('myPlaylist.txt', 'w');
        fwrite($file,$playlistMaster);
        fclose($file);
	 }
     
}    


/*
 * If library needs to be built
 */
if(isset($_GET['buildLibrary']))
{    
     ini_set("max_execution_time", "60000");                             
     //instatiate necessary classes for getting information
     $musicLibrary = new MusicLibrary();          
     $musicLibrary->buildRadioPlaylists();
     //get the files
     $files = $musicLibrary->buildFiles('music/');
    
     //serialize the files
     //and cache them into a text file
     $fileSerial = serialize($files);
     $fp = fopen('library.txt', 'w');
     fwrite($fp, $fileSerial);
     fclose($fp);
}
else //otherwise get the information from the text file
{           
     $files = unserialize(file_get_contents('library.txt')); 
}
        
 
?>
<div id="playlists">

</div>
<div id="playlist">
    <table>
    </table>
</div>
<div id="search">
	Search: <input type="text" id="searchBox" />
</div>
<div id="library">
    <table>

    <tr id="tableHeadings">
        <th>Title</th>
        <th>Artist</th>
        <th>Album</th>
        <th>Duration</th> 
    </tr>

    <?php 
    foreach($files as $file)
    {
          echo '<tr class="musicLink songRow" data="'.$file['filePath'].'" imagepath="'.$file['imagePath'].'">';

          $info = array('duration' => 'Na:Na', 
                        'title'    => $file['fileName'], 
                        'artist'   => '',
                        'album'    => '');
          if(isset($file['info']['playtime_seconds']))
          {
            $info['duration'] = 
                    number_format($file['info']['playtime_seconds']/60,0).':'.
                    str_pad($file['info']['playtime_seconds']%60,2,'0',STR_PAD_LEFT);
          }
          if(isset($file['info']['id3v1']['title']))
          {
            $info['title']  = $file['info']['id3v1']['title'];
            $info['artist'] = $file['info']['id3v1']['artist'];
            $info['album']  = $file['info']['id3v1']['album'];
          }
	  elseif(isset($file['info']['id3v2']['comments']['title'][0]))
	  {
	    $info['title'] = $file['info']['id3v2']['comments']['title'][0];
	    $info['artist'] = $file['info']['id3v2']['comments']['artist'][0];
	    $info['album']  = $file['info']['id3v2']['comments']['album'][0];
	  }
	  
	  
          echo '<td class="title">'.$info['title'].'</td>
                <td class="artist">'.$info['artist'].'</td>
                <td class="album">'.$info['album'].'</td>
                <td class="duration">'.$info['duration'].'</td>                                              
          </tr>';                                                                                 
    }              
      
    ?>  
    </table>
</div>
</body>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.js">
</script>
<script type="text/javascript" src="playerlogic.js"></script>
<script type="text/javascript">

</script>
</html>
