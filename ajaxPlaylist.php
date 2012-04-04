<?php
    if(isset($_REQUEST['savePlaylist'])){
		print_r($_REQUEST);
        $songs = json_decode($_REQUEST['songs'],true);
        print_r($songs);
        $playlistMaster = json_decode(file_get_contents('myPlaylist.txt'),true);
 
        if(!isset($playlistMaster[$_REQUEST['playlistName']]))//if there is no playlist
        {
            $playlistMaster[$_REQUEST['playlistName']] = $songs['Songs'];
        }
        else
        {
            $playlistMaster[$_REQUEST['playlistName']] = $songs;   
        }
                                            
        $playlistMaster = json_encode($playlistMaster);
        $file = fopen('myPlaylist.txt', 'w');
        fwrite($file,$playlistMaster);
        fclose($file);
        
        echo 1;
    }
    if(isset($_REQUEST['loadPlaylist']))
    {
        echo file_get_contents('myPlaylist.txt'); 
    }
    
    if(isset($_REQUEST['clearPlaylist']))
    {
		$playlistMaster = json_decode(file_get_contents('myPlaylist.txt'),true);
		$playlistMaster[$_REQUEST['playlistName']]['Songs'] = array();
		
		$file = fopen('myPlaylist.txt', 'w');
		fwrite($file, json_encode($playlistMaster));
		fclose($file);
        echo 1;
    }
    
    if(isset($_REQUEST['loadPlaylists']))
    {
        $playlistsJSON = json_decode(
                            file_get_contents(
                                        $_REQUEST['filename']
                                            )
                                     ,true);
        echo json_encode(array_keys($playlistsJSON));
        
    }
    
    if(isset($_REQUEST['initPlaylist']))
    {
        
        
        $playlistMaster = json_decode(file_get_contents('myPlaylist.txt'),true);
        
        $playlistMaster[$_REQUEST['playlistName']] = array();
        print_r($playlistMaster);
        $playlistMaster = json_encode($playlistMaster);
        $file = fopen('myPlaylist.txt', 'w');
        fwrite($file,$playlistMaster);
        fclose($file);
        
        
        
        echo 1;
    }
?>