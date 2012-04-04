/* this is a set of functions to handle the player
 * @author nrushton
 */
$audioObject = new Audio();

/*class
 *   class encapsulates the audio object and augments it's functionality to work with the playlist
 *   @constructor
 *    @var Audio Object - html5 audio object
 */
function AudioPlayer(audioInterface, timeleftDiv, gutter, progressBar,playToggle,volumeKnob)
{
    
    self = this;
	
	/*
	 * handlers for volume control
	 */
	this.volumePosition = 585;
	this.DEGREES_MIN   = 315;
	this.DEGREES_MAX   = 585;
	this.VOLUME_TICK = 1/((self.DEGREES_MAX - self.DEGREES_MIN)/5); //volume range is between 0 and 1, so we must put the ticks under 1, each degree counts for 
    
	/*number of seconds to initially seek when using arrows to seek*/
	this.INITIAL_SEEK_TICK = 1;
	/*times*/
    this.remaining = 0;
    
    /*visual elements*/
    this.timeleft = timeleftDiv;
    this.gutter   = gutter;
    this.progressBar = progressBar;
    this.playToggle = playToggle;
    this.audio = audioInterface;
	this.volumeKnob = volumeKnob;
	this.lastYPosition = 0;
	
	/*
	 * method bind to gutter to be able to click on the progress bar and go to that position
	 * e - event object
	 */
	$('#gutter').click(function(e){
			if(self.audio.seekable.length > 0)
			{
				var offset = $(this).offset();
				var percentClicked = (e.pageX - offset.left)/$(this).width();
				var timeToChangeTo = self.audio.duration * percentClicked;
				self.seek(timeToChangeTo, true);
			}
		})
		
	/*
	 * binds keypress to a function that handles various key controls for the player
	 * e - event object
	 * current bindings
	 * + - increase volume
	 * - - decrease volume
	 * ` - for pause/play
	 * right arrow - seek forward
	 * left arrow  - seek backwards
	 */
	$(document).bind('keydown',function(e){
		var code = e.which;
		console.log(code);
		var d = new Date();
		switch(code)
		{
			case 192: //tilda
					e.preventDefault();
					self.togglePlayPause();
				break;
			case 107://+ button: volume up 
			    if(self.audio.volume < 1){
				    var desiredVol = self.audio.volume + .05;
					var volume = (desiredVol > 1   ? 1 : desiredVol);
					self.setVolume(volume);
				}
				break;
			case 109: //- button: volume down
				if(self.audio.volume >= 0){
				    var desiredVol = self.audio.volume - .05;
					var volume = (desiredVol < .05 ? 0 : desiredVol);
					self.setVolume(volume);
				}
				break;
			case 39: //seek forward
				self.seek(self.audio.currentTime + self.INITIAL_SEEK_TICK);
				break;
			case 37: //seek backward
				self.seek(self.audio.currentTime - self.INITIAL_SEEK_TICK);
				break;
		}
	});
	
	/* bindings for the volume knob
	 * on mouse down, the function binds another method for detecting the mouse position and moving the wheel accordingly
	 * on mouse up, the method unbinds the mousemove event and unbinds itself
	 */
	$(this.volumeKnob).mousedown(function(){
		$(document).bind('mousemove',
		function(e)
		{
		     
			if(e.pageY < self.lastYPosition)
			{
				if((self.volumePosition) < self.DEGREES_MAX){
					self.setVolume(self.audio.volume + self.VOLUME_TICK);
				}
			}
			else if(e.pageY > self.lastYPosition)
			{
				if((self.volumePosition) > self.DEGREES_MIN)
				{
					self.setVolume(self.audio.volume - self.VOLUME_TICK);
				}
			}
			
			self.lastYPosition = e.pageY;
		});
		
		$(document).mouseup(
		function()
		{
			$(document).unbind('mousemove')
			$(document).unbind('mouseup');
		});
	
	});
	
	/* time update binding
	 * every time the time is updated, the method will change the progress bar accordingly
	 */
    $(this.audio).bind('timeupdate',function(){
		var time = 0;
		if(isFinite(parseInt(self.audio.duration)))
		{
			time = parseInt(self.audio.duration - self.audio.currentTime, 10);
		}
		else
		{
			time = parseInt(self.audio.currentTime,10);
		}
		
		self.timeleft.html(self.getFormattedTime(time));

        	self.setProgressBar(self.getProgress() + '%',false);

        }
    )
	
	/*
	 *binding for the play/pause button
	 */
    this.playToggle.click(function(){
			self.togglePlayPause();
        }
    )
	
	/* setVolume(val)
	 * val - double(0,1) desired volume
	 * abstraction of the native audio interface's volume set
	 * handles rotation of the volume knob 
	 */
	this.setVolume = function(val)
	{
		var val = (val < 0 ? 0 : val > 1 ? 1 : val);
		this.audio.volume = val;
		self.volumePosition = self.DEGREES_MIN + ((self.DEGREES_MAX - self.DEGREES_MIN) * (val/1)); //degree range * desired volume / max volume
		$(self.volumeKnob).css('-webkit-transform', 'rotate('+self.volumePosition+'deg)');
	}
	
	/*seek
	 * seeks to a paticular time in a song
	 * seconds - double - seconds to seek to in the song
	 */
	this.seek = function(seconds,animate){
		if(this.audio.duration > seconds) //if a valid seekable time is passed in
		{
			this.audio.currentTime = seconds;
		}
		
				
	}

	/*
	 *@var int - number of seconds to convert
	 *@return string - formatted time
	 */
	this.getFormattedTime = function(seconds)
	{
		var days    = Math.floor(seconds/60/60/24);
		var hours   = Math.floor(seconds/60/60) - (days  * 24);
		var minutes = Math.floor(seconds/60)    - (hours * 60);
		var seconds = seconds % 60;

		return (days    > 0 ? days + ':' : '')  +
		       (hours   > 0 ? hours + ':' : '') + 
		       (minutes > 9 ? minutes : ('0'+ minutes)) + ':' +
		       (seconds > 9 ? seconds : ('0'+ seconds));
	}

	/*
         *@return returns the progress out of 100
	 */
	this.getProgress = function()
	{
		return (self.audio.currentTime / self.audio.duration) * 100;
	}

	
	/*setProgressBar
	 * sets the width of the progress bar, needs to be abstracted to allow for instant and animated movement
	 * width - string - representation in px, %, etc
	 * animate - bool - boolean, animates when true
	 */
	this.setProgressBar = function(width,animate)
	{
		if(animate) {
			self.progressBar.animate({"width" : width});
		}
		else{
			self.progressBar.css({'width': width});
		}
		
	}
	
	/*
	 *retrieves the html5 audio interface
	 */
	this.getInterface =
	function()
	{
		return this.audio;
	}
	
	/*
	 * abstraction of the play/pause methods from the html5 audio object
	 * plays when paused, pauses when playing
	 */
	this.togglePlayPause =
	function()
	{
            if(!self.audio.paused)
            {
                   self.playToggle.css('background-position', '0px 0');
                   self.audio.pause();
            }
            else
            {
                   self.playToggle.css('background-position', '50px 0');
                   self.audio.play();
            }	
	}
    
	/*
	 * abstraction of the play, load, and src from the html5 audio element
	 * songPath - string - path of the song to be played
	 * imagePath - string - path of the image for the progress gutter
	 */
    this.play  = 
    function(songPath,imagePath)
    {
		if(imagePath != '')
		{
			this.playToggle.css('background-position', '50px 0');
			this.gutter.css(
				'background-image', 
				'url(' + imagePath +')'
			);
		}
        this.audio.src = songPath;
        this.audio.load();
        this.audio.play();
    }
    
	/*
	 * tells whether the html5 audio element is in a playing state or not
	 * @return bool
	 */
    this.isPlaying =
    function()
    {
        try{      
            return this.audio.seekable.end() != $audio.currentTime;
            
        }
        catch(err)
        {
             return this.audio.currentTime != 0
        } 
    }
}



/*Class for audio logic and ui logic, accepts a player as an aggregate
 */
function Playlist(playlistsFile, playlists, playlist,library,audioInterface)
{
    
    var self = this;
    this.audioPlayer = audioInterface;
    /* Cache and data files 
     *    Playlistsfile contains all playlist data in JSON
     */
    this.playlistsFile = playlistsFile;   
    
    /*
     * Contains all names of the playlists
     */
    this.playlistNames = new Array();
    /*
     * Contains the current songs
     */

    this.currentSongs = new Array();
    this.loadPlaylists =
    function()
    {
        var $playlistNames = null;
       $.ajax( //load the json string of playlist names
                {
                    url: 'ajaxPlaylist.php',
                    type: 'post',
                    data: "loadPlaylists=1&filename=" + this.playlistsFile,
                    success:  
                        function (data) //set the values in the playlist names array
                        {
                            $playlistNames = $.parseJSON(data); 
                             
                            $.each($playlistNames,
                                function(key,val)
                                {  
                                    self.playlistNames.push(val);
                                     
                                }     
                            );
                            
                                self.display();
                        }
                }
                );  
                 
    }
    
    this.loadPlaylist =
    function(playlistName)
    {        
         $.ajax(
                {
                    url: 'ajaxPlaylist.php',
                    type: 'post',
                    data: "loadPlaylist=1&playlistName=" + playlist,
                    async : false,
                    success:  
                    function(data)
                    {
                        var playlists = $.parseJSON(data);
						if(playlists[playlistName].Songs != null)
							self.currentSongs = playlists[playlistName].Songs;
						else
							self.currentSongs = [];
						$('#playlist').children('table').html('');
                        var songs = self._getHTMLFormattedSongs();
                        $.each(songs,
                             function (key,val) 
                             { 
                                $('#playlist').children('table').append(val) 
                             } )
                    }
                }
                );    
    }
    
    this.display =
    function()
    {
            console.log(this.playlistNames);
        $.each(this.playlistNames,
            function(key,val)
            {
                var playlistElement = $('<a href="javascript://">' + val + '</a>'); 
                playlistElement.click( 
                function()
                {   
                   $('#playlists').children('a').removeClass('selectedPlaylist');
                   
                   $(this).addClass('selectedPlaylist');   
                   self.loadPlaylist(val,
                   function(data)
                   {

                   });
                   
                }); 
                
                $('#playlists').append(playlistElement);
                 
                
            });
            
    }
    
    /*
     * array of jQuery table row elements
     */
    this._getHTMLFormattedSongs =
    function()
    {       
        var elements = new Array();       
        $.each(this.currentSongs,
                function(key, val)
                {
                    $songElement = $('<tr class="songRow" data="'  + val.path + '" imagepath="'+ val.imagepath +'">\
                        <td class="title">'    + val.title    + '</td>\
                        <td class="artist">'   + val.artist   + '</td>\
                        <td class="album">'    + val.album    + '</td>\
                        <td class="duration">' + val.duration + '</td>\
                       </tr>'
                     )
                    $songElement.click(
                        function()
                        {
                            
                            $('#playlist').find('tr.playing').removeClass('playing').addClass('played');
                            $(this).removeClass('played').addClass('playing');  
                            self.audioPlayer.play(val.path,val.imagepath);
                        }
                    )
                    elements.push($songElement);
                }
             )
             return  elements;
    }
	
	this.savePlaylist =    function(playlistName, callback)
   {    
        var playlist = 
        {   
            "Songs" : this.currentSongs
        };      
        $.ajax(
            {
                type: 'post',
                url:  'ajaxPlaylist.php',
                data: {savePlaylist : 1, playlistName : playlistName, songs : JSON.stringify(playlist)}
            }
        )
   }
   
   this.addSong = function(song)
   {
      this.currentSongs.push(song);
   }
   
   this.clearPlaylist = function(playlistName)
   {
      $.ajax
	  (
	    {
			type: 'post',
			url:  'ajaxPlaylist.php',
			data: 'clearPlaylist=1&playlistName=' + playlistName,
			success:
				function()
				{
					$(playlist).find('table').html('');
				}
		}
	  );
   }
   
   this.createPlaylist =
   function(playlistName)
   {
		$(playlists).find('a').removeClass('selectedPlaylist');
		$(playlists).append('<a href="javascript://" class="selectedPlaylist">' + playlistName + '</a>');
		
		$.ajax
		(
			{
				type: 'post',
				url:  'ajaxPlaylist.php',
				data: 'initPlaylist=1&playlistName=' + playlistName,
				success:
				function()
				{
					self.loadPlaylist(playlistName);
				}
			}
		)
   }
   
   this.playNext = function(reverse)
   {
		var song = null;
		if($(playlist).find('tr.playing').next().length != 0)
			song = $(playlist).find('tr.playing').next();
		else if($(playlist).find('tr').length == 0)
			return;
		else
			song = $(playlist).find('tr:first');
		
		$(playlist).find('tr.playing').removeClass('playing').addClass('played');
		song.addClass('playing').removeClass('played');
		this.audioPlayer.play(song.attr('data'),song.attr('imagepath'));
   }
}   
$audio = new AudioPlayer($audioObject,$('#timeleft'),$('#gutter'),$('#progress'),$('#playToggle'),$('#volumeKnob')); 
$playlist = new Playlist('myPlaylist.txt', '#playlists', '#playlist','#library',$audio);
$playlist.loadPlaylists();   

$('#clearPlaylist').click(
	function()
	{
		$playlist.clearPlaylist($('#playlists .selectedPlaylist').html());
	}
);
$($audio.getInterface()).bind('ended', 
	function()
	{
		$playlist.playNext();
	}
)
$('#addPlaylist').click(
	function  ()
	{
	   $userInput = prompt('Enter a playlist name:');
	   $playlist.createPlaylist($userInput);
		
	}
)

$('#searchBox').keyup(
function(e)
{
	var code = (e.keyCode ? e.keyCode : e.which);
	if(code == 32)
	{
		$(this).html($(this).html() + ' ');
	}
	var reg = new RegExp($(this).val(), 'i');
	$('#library table tr').each(
		function()
		{
			$(this).find('td').each(
			function()
			{
				if(!reg.test($(this).html()))
				{
					$(this).parent().hide();
				}
				else
				{
					$(this).parent().show();
					return false;
				}
			});
		});
})
/*
 * binds each table row in the library to add to the playlist   
 */
$('#library tr').click(
function()
{ 
   if($('#playlists .selectedPlaylist').length != 0)
   {
	   $row = $(this).clone();
	   $row.appendTo('#playlist table');  
	   $row.click(
		   function()
		   {
			   $('#playlist tr.playing').removeClass('playing').addClass('played');
			   $(this).addClass('playing').removeClass('played');
			   
			   $audio.play($(this).attr('data'), $(this).attr('imagepath'));  
		   }
	   );                      
	   if(!$audio.isPlaying()){ //if there is nothing playing play the song when clicked
		   $('#gutter').css('background-image', $(this).attr('imagepath'));
		   $audio.play($(this).attr('data'), $(this).attr('imagepath'));
		   $row.addClass('playing');
	   }
	   $('#playlist table tr').each(
			function()
			{
				
			}
	   )
	   
	   var song = { 
			"title"    : $(this).find('.title').html(),
			"album"    : $(this).find('.album').html(),
			"duration" : $(this).find('.duration').html(),
			"artist"   : $(this).find('.artist').html(),
			"path"     : $(this).attr('data'),
			"imagepath": $(this).attr('imagepath')
	   };
	   $playlist.addSong(song);
	   
	   $playlist.savePlaylist($('#playlists a.selectedPlaylist').html());
   }
});
