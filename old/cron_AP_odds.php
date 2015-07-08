<?php
if(isset($_REQUEST['IdLeague']) && strcasecmp(trim($_REQUEST['IdLeague']),"")!==0) { $argv[1] = $_REQUEST['IdLeague']; }
if(isset($_REQUEST['BannerAbTrue']) && strcasecmp(trim($_REQUEST['BannerAbTrue']),"")!==0) { $argv[2] = $_REQUEST['BannerAbTrue']; }
if(isset($_REQUEST['BannerAbFalse']) && strcasecmp(trim($_REQUEST['BannerAbFalse']),"")!==0) { $argv[3] = $_REQUEST['BannerAbFalse']; }
if(isset($_REQUEST['SectionTitle']) && strcasecmp(trim($_REQUEST['SectionTitle']),"")!==0) { $argv[4] = $_REQUEST['SectionTitle']; }

$banner_ab_true = "Off";
$banner_ab_false = "Off";


if(isset($argv[1]) AND strcasecmp($argv[1], intval($argv[1])) == 0){
    $id_league = $argv[1];
}

if(isset($argv[2])){
    $banner_ab_true = $argv[2];            
}

if(isset($argv[3])){
    $banner_ab_false = $argv[3];        
}

if(isset($argv[4])){    
    $section_title = $argv[4];        
}

$id_league = "919";
$sections = array("AMERICAN PHAROAH WINS", "3 YEAR OLD SEASON UNDEFEATED", "AMERICAN PHAROAH NEXT RACE");
$OutputPathFile = "/home/ah/allhorse/public_html/misc/odds_AP_xml.php";


generateOuput($id_league, $OutputPathFile, $banner_ab_true, $banner_ab_false, $sections);




function generateOuput($id_league, $OutputPathFile, $banner_ab_true = "Off", $banner_ab_false = "Off", $sections = array() ){
    $string = file_get_contents("http://ww1.betusracing.ag/odds.xml"); 
    $xml = new SimpleXMLElement($string);
    $global_results = array();


    $banners = $xml->xpath('/xml/league[@IdLeague="'.$id_league.'"]/banner');
    $banners_vtm = array();
    $banners_htm = array();
    $k = 0;

    foreach($banners as $b){
        $banners_vtm[$k] = (string)$b->attributes()['vtm'];
        $banners_htm[$k] = (string)$b->attributes()['htm'];
        ++$k;
    }

    $games = $xml->xpath('/xml/league[@IdLeague="'.$id_league.'"]/game');
    $index_banner = 1;
    $captions = array();
    $captions["title"] = array( 
        "vtm" => ucwords(strtolower($banners_vtm[0])), 
        "htm" => ucwords(strtolower($banners_htm[0])) );

    foreach($games as $g){
        $c = trim((string)$g->attributes()['vtm']);

        if(!isset($captions[$c])){
            $captions[$c] = array( 
                "vtm" => ucwords(strtolower($banners_vtm[$index_banner])), 
                "htm" => ucwords(strtolower($banners_htm[$index_banner])) );
            ++$index_banner;
        }
    }


    foreach ($sections as $title) {
        $result_t = $xml->xpath('/xml/league[@IdLeague="'.$id_league.'"]/game[@vtm="'.$title.'"]');
        
        if(count($result_t) > 0){
            $global_results[$title] = $result_t;
        }

        unset($result_t);
    }

    //$result = $xml->xpath('/xml/league[@IdLeague="'.$id_league.'"]/game[@vtm="'.$section_title.'"]');
    $parent_node = $xml->xpath('/xml/league[@IdLeague="'.$id_league.'"]');

    if(isset($parent_node) AND count($parent_node)>0){
        $parent_node_attributes = $parent_node[0]->attributes();    
    }

    ob_start();
    $result_count = count($global_results);

    if(count($global_results)>0){
        $table_title = $parent_node_attributes['Description'];
        $array_contenders = array();
        ?>
        <div class="table-responsive">
            <table  class="data table table-condensed table-striped table-bordered" width="100%" cellpadding="0" cellspacing="0" border="0"  summary="<?php echo $table_title; ?>">
                <caption><?php echo $captions["title"]["vtm"]; ?><br /><?php echo $captions["title"]["htm"]; ?></caption>
                <tbody>
        <?php
        foreach($global_results as $title=>$result){
            unset($game_attributes);
            unset($array_contenders);
            unset($game_children);
            unset($game_node);
            unset($line_attributes);
            unset($array_contenders);
            $i = 0;

        while(list( ,$game_node) = each($result)) {
            $game_attributes = $game_node->attributes();            
            $array_contenders[$i]['name'] = $game_attributes['htm'];

            list(, $game_children) = each($game_node->children());                    

            if(is_array($game_children)){
                while(list( ,$line_node) = each($game_children)) {
                    $line_attributes = $line_node->attributes();                    
                    $array_contenders[$i]['odds'][] = fractional_odds_calculate($line_attributes);
                }
            } else {
                $line_attributes = $game_children->attributes();
                $array_contenders[$i]['odds'][] = fractional_odds_calculate($line_attributes);
            }
            $i++;           
        } ?>        <tr>   
                        <th colspan="3" class="center">
                            <?php
                            echo $captions[$title]["vtm"];
                            if($captions[$title]["htm"] != "")
                                echo "<br />" . $captions[$title]["htm"];
                            ?>
                        </th>
                    </tr>
                    <tr>   
                        <th>&nbsp;</th>            
                        <th>American Odds</th>            
                        <th>Fractional Odds</th>            
                    </tr>
                    <?php for($i=0;$i<count($array_contenders);$i++) {?>
                    <tr>
                        <td>
                            <?php echo ucwords(strtolower($array_contenders[$i]['name'])); ?>
                        </td>
                        <td>                    
                            <table class="fer" style="width:100%">
                                <?php $array_odds = $array_contenders[$i]['odds'];
                                for($j=0;$j<count($array_odds);$j++) { ?>
                                <tr>
                                    <td><?php echo ucwords(strtolower($array_odds[$j]['oddsh'])); ?></td>
                                </tr>
                                <?php } ?>
                            </table>
                        </td>
                        <td>
                            <table class="fer" style="width:100%">
                                <?php $array_odds = $array_contenders[$i]['odds'];
                                for($j=0;$j<count($array_odds);$j++) { ?>
                                <tr>                                
                                    <td><?php echo ucwords(strtolower($array_odds[$j]['fractional_odd'])); ?></td>
                                </tr>
                                <?php } ?>
                            </table>
                        </td>
                    </tr>
            <?php } 
                unset($result);
            } //end foreach ?>
                    <tr>      
                        <td class="dateUpdated center" colspan="5"><em>Updated <?php echo date("F j, Y");?>. Bet US Racing - Official <a href="http://www.usracing.com/bet-on/american-pharoah">American Pharoah Odds</a>. <br>All odds are fixed odds prices. </em> </td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php   } // end if ?>

    <?php $message = "";

    if( $id_league != "" && $result_count>0 ){
        $message = ob_get_clean();
    } else{
  $message = "The odds are currently being updated, please check back shortly. <em> - Updated ".date("F j, Y").". </em>";
    }

    //echo $message;
    file_put_contents($OutputPathFile, $message);
}

function fractional_odds_calculate(& $line_attributes){
    $row = array();
    $row['oddsh'] = $line_attributes['oddsh'];
    $oodsh = intval($line_attributes['oddsh']);

    if(intval($oodsh)>0){
       $row['fractional_odd'] = float2rat($oodsh/100);     
    } else if($oodsh<0) {
        $row['fractional_odd'] = float2rat(100/(-$oodsh));
    } else {
        $row['fractional_odd'] = $line_attributes['oddsh'];   
    }

    return $row;
}

function float2rat($n, $tolerance = 1.e-6) {
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
