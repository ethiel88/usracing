<?php
if(!class_exists("OddsGeneratorXP")){
	class OddsGeneratorXP{
		public $ALWAYSWRITE;
		public $OUTPUTFILEPATH;
		public $IdLeague;
		private $data;
		private $mainTitle;
		
		function __construct($param1, $param2, $param3){
			$this->ALWAYSWRITE		= $param1;
			$this->OUTPUTFILEPATH	= $param2;
			$this->IdLeague 			= $param3;
			$this->data					= array();
			$this->mainTitle			= "";
		}
		
		function process(){
			$this->parseXmlStructureXP();
			$this->generateOutputXP();
		}
		
		function parseXmlStructureXP(){
			global $IdLeague, $data, $mainTitle;
			$ret = false;
			$mainTitle = "";
			$data = array();
			$xml_string = file_get_contents("http://ww1.betusracing.ag/odds.xml"); 
			$matches = array();
			preg_match('/<league IdLeague="'.$IdLeague.'"[^>]*>(.*?)<\/league>/', $xml_string, $matches);
			if(count($matches)<=0)return $ret;

			$league = $matches[1];
			$leagueObj = simplexml_load_string($matches[0]);
			$mainTitle = ucwords(strtolower((string) $leagueObj["Description"]));

			$sections = preg_split("/<banner[^>]*>/", $league); // split by sections
			if(count($sections)<=0) return $ret;

			$banners = array();
			preg_match_all("/<banner[^>]*>/", $league, $banners); // for getting the banners
			if(count($banners)<=0) return $ret;
			$banners = $banners[0];

			for($i=0; $i< count($banners); $i++) {
				$bannerParams =  simplexml_load_string($banners[$i]);
				$section = $sections[$i+1];
				$games = array();

				if($section != ""){
					$g = array();
					preg_match_all("/<game[^>]*>.*?<\/game>/", $section, $g);

					foreach($g[0] as $gamesPlain){
						$linesPlain = array();
						preg_match_all("/<line[^>]*>/", $gamesPlain, $linesPlain);
						$lines = array();

						foreach ($linesPlain[0] as $line) {
							$lineParams = simplexml_load_string($line);
							$lines[] = array(
								"tmname" => ucwords(strtolower((string)$lineParams[0]["tmname"])),
								"odds" => (string)$lineParams[0]["odds"],
								"oddsh" => (string)$lineParams[0]["oddsh"]
								);
						}
					
						$gameObj = simplexml_load_string("<document>".$gamesPlain."</document>");
						$games[] = array(
						"vtm" => ucwords(strtolower((string)$gameObj->game["vtm"])),
						"htm" => ucwords(strtolower((string)$gameObj->game["htm"])),
						"lines" => $lines
						);
					}
				}

				$data[] = array(
					"ab" => (string) $bannerParams[0]["ab"],
					"vtm" => ucwords(strtolower((string) $bannerParams[0]["vtm"])),
					"htm" => ucwords(strtolower((string) $bannerParams[0]["htm"])),
					"games" => $games
					);
			}
			
			return true;
		}

		function generateOutputXP(){
			global $data, $mainTitle;
			$output = "";
			if(count($data)<=0) return $this->writeOutputXP($output);

			$firstHeader = false;
			ob_start();
		?>
		<div class="table-responsive">
			<table  border="1" class="data table table-condensed table-striped table-bordered" width="100%" cellpadding="0" cellspacing="0" border="0"  summary="<?php echo $mainTitle; ?>">
				<caption><?php echo $mainTitle; ?></caption>
				<tbody>
		<?php
			foreach ($data as $d) {
		?>
				<tr>   
						<th colspan="3" class="center">
						<?php
						echo $d["vtm"];
						if($d["htm"] != "") echo "<br />" . $d["htm"];
						?>
						</th>
				</tr>
		<?php
				if(count($d["games"])>0){
					foreach($d["games"] as $g){
						if(count($g["lines"])>0){
							if($g["htm"]!="")
								echo '<tr><th colspan="3" class="center">'.$g["htm"].'</th></tr>';
							if(!$firstHeader){
								echo '<tr><th>Team</th><th>American Odds</th><th>Fractional Odds</th></tr>';
								$firstHeader = true;
							}
							foreach($g["lines"] as $l){
								if($l["tmname"]!="")$team = $l["tmname"];
								else $team = $g["vtm"] . " " . $g["htm"];
								echo '<tr>';
								echo '<td>'.$team.'</td>';
								echo '<td>'.$l["oddsh"].'</td>';
								echo '<td>'.$this->fractionalCalculationXP($l["oddsh"]).'</td>';
								echo '</tr>';
							}
						}
					}
				}
			}
		?>
						<tr>
							<td class="dateUpdated center" colspan="5">
								<em id='updateemp'>Updated <?php echo date("F j, Y H:i:s");?>.</em> 
								Bet US Racing - Official <a href="http://www.usracing.com/kentucky-derby/odds">Kentucky Derby Odds</a>. <br />
								All odds are fixed odds prices.
							</td>
						</tr>
				</tbody>
			</table>
		</div>
		<?php
			$output = ob_get_clean();
			return $this->writeOutputXP($output);
		}

		function fractionalCalculationXP($oddsh){
			$oodsh_val = intval($oddsh);
			$fractional = 0;

			if(intval($oodsh_val)>0){
				$fractional  = $this->float2ratXP($oodsh_val/100);     
			} else if($oodsh_val<0) {
				$fractional  = $this->float2ratXP(100/(-$oodsh_val));
			} else {
				$fractional  = $oddsh;   
			}

			return $fractional;
		}

		function float2ratXP($n, $tolerance = 1.e-6) {
			$h1=1; $h2=0;
			$k1=0; $k2=1;
			$b = 1/$n;
			do {
				$b = 1/$b;
				$a = floor($b);
				$aux = $h1; $h1 = $a*$h1+$h2; $h2 = $aux;
				$aux = $k1; $k1 = $a*$k1+$k2; $k2 = $aux;
				$b = $b-$a;
			} while (abs($n-$h1/$k1) > $n*$tolerance);

			return "$h1/$k1";
		}

		function writeOutputXP($contents){
			global $ALWAYSWRITE, $OUTPUTFILEPATH;
			$update = " - Updated " . date("F j, Y H:i:s");
			if($contents == ""){
				$contents = "The odds are currently being updated, please check back shortly. <em id='updateemp'> $update. </em>";
				if($ALWAYSWRITE || !file_exists($OUTPUTFILEPATH)){
					if(file_put_contents($OUTPUTFILEPATH, $contents)){
						echo "File " , $OUTPUTFILEPATH . " was written with no data.\n";
						return true;
					}
					else{
						echo "Error while trying to write file " . $OUTPUTFILEPATH . " with no data. Please check permissions.\n";
						return false;
					}
				}
				else{
					$contents = preg_replace("/(<em\sid=\'updateemp\'>)(.*?)(<\/em>)/", "$1 ".$update." $3", file_get_contents($OUTPUTFILEPATH) );
					if($contents !== NULL){
						if(file_put_contents($OUTPUTFILEPATH, $contents)){
							echo "Empty output, update record updated.\n";
							return true;
						}
						else{
							echo "Error while trying to write file " . $OUTPUTFILEPATH . " with no data but updating update record. Please check permissions.\n";
							return false;
						}
					}
					
					return false; 
				}
			}
			else{
				if(file_put_contents($OUTPUTFILEPATH, $contents)){
					echo "File " , $OUTPUTFILEPATH . " was written.\n";
					return true;
				}
				else{
					echo "Error while trying to write file " . $OUTPUTFILEPATH . ". Please check permissions.\n";
					return false;
				}
			}

		}
	} //end class
} //endif


$ALWAYSWRITE = FALSE; //always write eveng with the message "please check back shortly"
$OUTPUTFILEPATH = "/home/ah/allhorse/public_html/breeders/odds_distaff_xml.php"; //the absolute path of the file to be written
$IdLeague = "928";
$obj = new OddsGeneratorXP($ALWAYSWRITE, $OUTPUTFILEPATH, $IdLeague);
$obj->process();
