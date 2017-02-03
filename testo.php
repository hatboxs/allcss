<?php
error_reporting(E_ERROR | E_PARSE);
$url = "https://drive.google.com/file/d/0B9boU3jloRIsLTNVOGVxMU9wTms/view&json=jwplayer";
class gdrive
{
	protected $folder;

	protected $path;

	protected $url;

	protected $itag = [
		37,
		22,
		59,
		18
	];

	 protected $vidcode = [
	 	//2D Non-DASH
        '18'	=> '360',
        '59'	=> '480',
        '22'	=> '720',
        '37'	=> '1080',
        //3D Non-DASH
        '82'	=> '360',
        '83'	=> '240',
        '84'	=> '720',
        '85'	=> '1080'
    ];

	public function __construct($folder='')
	{
		$this->folder = $this->createPath($folder);
	}

	public function setItag($itags)
	{
		if(is_array($itags)) array_merge($this->itag, $itags);
	}

	public function setVidcode($vidcode)
	{
		if(is_array($vidcode)) array_merge($this->vidcode, $vidcode);
	}

	public function getLink($url)
	{
		$id = $this->getDriveId($url);
		if($id){

			$headers = $this->getHeaders();

			if ($headers['http_code'] === 200 and $headers['download_content_length'] < 1024*1024 and $this->download()) {
				
				unset($headers);
				$file = fopen($this->path, "r") or die("Unable to open file!");
				$body = fgets($file);

				if(strpos($body,'status=fail') !== false ) return false;

				$fmt = $this->fetchValue(urldecode($body), 'fmt_stream_map=','&fmt_list');

				$urls = explode(',', $fmt);
				$source = [];
				foreach ($urls as $url) {
					list($itag,$link) = explode('|', $url);
					if(in_array($itag, $this->itag)){
						if($itag == 37) {
$source	.= '{type: "mp4", label: "1080p", file: "'.preg_replace("/\/[^\/]+\.google\.com/","/redirector.googlevideo.com",$link).'?title=FULLHD/1080p"},';}
		if($itag == 22) {$source	.= '{type: "mp4", label: "720p", file: "'.preg_replace("/[^\/]+\.googlevideo\.com/","redirector.googlevideo.com",$link).'?title=HD/720p"},';}
        if($itag == 59) {$source	.= '{type: "mp4", label: "480p", file: "'.preg_replace("/\/[^\/]+\.google\.com/","/redirector.googlevideo.com",$link).'?title=SD/480p"},';}
		if($itag == 18) {$source	.= '{type: "mp4", label: "360p", file: "'.preg_replace("/\/[^\/]+\.google\.com/","/redirector.googlevideo.com",$link).'?title=SD/360p", "default": "true"},';}
						//$source[$this->vidcode[$itag]] = preg_replace("/[^\/]+\.googlevideo\.com/","redirector.googlevideo.com",$link);
						
					}
				}
				@unlink($this->path);
				return $source;
			}	
		}
		return false;
	}

	private function getDriveId($url)
	{
		preg_match('/(?:https?:\/\/)?(?:[\w\-]+\.)*(?:drive|docs)\.google\.com\/(?:(?:folderview|open|uc)\?(?:[\w\-\%]+=[\w\-\%]*&)*id=|(?:folder|file|document|presentation)\/d\/|spreadsheet\/ccc\?(?:[\w\-\%]+=[\w\-\%]*&)*key=)([\w\-]{28,})/i', $url , $match);

		if(isset($match[1])){
			$id = $match[1];
			$this->url = sprintf('https://docs.google.com/get_video_info?docid=%s', $id);
			$this->path = $this->folder . $id;

			return $id;
		}
		
		return false;
	}

	private function createPath($folder)
	{
	    if (is_dir($folder)) return $folder;
	    $prev_path = substr($folder, 0, strrpos($folder, '/', -2) + 1 );
	    $return = $this->createPath($prev_path);
	    return ($return && is_writable($prev_path)) ? mkdir($folder, 0777) : false;
	}

	private function getHeaders()
	{
		$ch = curl_init($this->url);
		curl_setopt( $ch, CURLOPT_NOBODY, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, false );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 3 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_exec( $ch );
		$headers = curl_getinfo( $ch );
		curl_close( $ch );

		return $headers;
	}

	private function download()
	{
		$fp = fopen($this->path, 'w+');
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $this->url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, false );
		curl_setopt( $ch, CURLOPT_BINARYTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 50 );
		curl_setopt( $ch, CURLOPT_FILE, $fp );
		curl_exec( $ch );
		curl_close( $ch );
		fclose( $fp );

		if (filesize($this->path) > 0) return true;
	}

	private function fetchValue($str, $find_start, $find_end)
	{
		$start = stripos($str, $find_start);

		if($start==false) return '';

		$length = strlen($find_start);
		$end = stripos(substr($str, $start+$length), $find_end);
		return trim(substr($str, $start+$length, $end));
	}
}
$destination = __DIR__ . '/tmp/';

$drive = new gdrive($destination);
echo "<style>
    body,
    html {
        background-color: #000;
        margin: 0px;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }
    
    #player {
        height: 100% !important;
    }
</style>
<title>Player</title>
<script src='https://content.jwplatform.com/libraries/e7JeZyuF.js'></script>
	<div id='player'><img src='/play/loading-video.gif' height='100%' width='100%'></div>
	<script type='text/javascript'>
	      
	var playerInstance = jwplayer(player);
             playerInstance.setup({
		sources: [".str_replace( 'Array', '', $drive->getLink($url) )."],
                tracks: [
                    {file: '/srt/100.Streets.2016.WEB-DL.x264-FGT.srt', label: 'En',kind: 'captions'},
                    {file: '/srt/Alleycats.2016.BluRay-GubrakZ.srt', label: 'Id',kind: 'captions',default:'true'}
                ],
		image: '',
		
	        aspectratio: '16:9',
		fullscreen: 'true', 
		autostart: 'false',
                abouttext: 'Antoncabon',
                aboutlink: 'http://www.antoncabon.us/',
                captions: {
	        color: '#ffffff',
	        fontSize: 14,
	        backgroundOpacity: 10
	}
		});
             
             playerInstance.addButton(
                '/play/download.png',
                'Download Movie', 
        
        function(){
	window.location.href = jwplayer().getPlaylistItem()['file'];
	}, 'download'
	);
	</script>
";
?>
