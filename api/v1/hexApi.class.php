<?

require_once 'api.class.php';

class hexApi extends API
{

    /**
     * User endpoint to get user data from Riot API and analyze it
     */
    protected function user() {
        if ($this->method == 'GET') {

            /* Validate data & setup variables and error handling
            ===================================== */  
            $valid_regions = array('na', 'br', 'eune', 'euw', 'lan', 'las', 'oce', 'tr');
            if(empty($this->args) || empty($this->verb))
                return 'Invalid Endpoint';
                        
            if(!in_array($this->verb, $valid_regions))
                return (object)array('error' => 'Sorry, that region is not supported');
                        
            $summoner_name = $this->args[0];
            $region = $this->verb;
            
            $altRegion = 'na1';
            switch ($region) {
                case 'na': $altRegion = 'na1'; break;
                case 'br': $altRegion = 'br1'; break;
                case 'eune': $altRegion = 'eun1'; break;
                case 'euw': $altRegion = 'euw1'; break;
                case 'lan': $altRegion = 'la1'; break;
                case 'las': $altRegion = 'la2'; break;
                case 'oce': $altRegion = 'oc1'; break;
                case 'tr': $altRegion = 'tr1'; break;
            }

            // Set up error handling
            set_error_handler(create_function(
                    '$severity, $message, $file, $line',
                    'throw new ErrorException($message, $severity, $severity, $file, $line);'
                )
            );
            
            /* Query API to get basic summoner data
            ===================================== */  
            $url = 'https://' . $region . '.api.pvp.net/api/lol/' . $region . '/v1.4/summoner/by-name/' . rawurlencode($summoner_name) . '?api_key=' . $this->riotAPIKey;
            
            $data = new stdClass();

            try {
                $response_code = $this->get_http_response_code($url); 
                if($response_code != "200"){
                    if($response_code == "404")
                        return (object)array('error' => 'Invalid User');
                    return (object)array('error' => 'Riot\'s a little too busy, try again later (1'.$response_code.')');
                }
                $result = file_get_contents($url);
            }
            catch (Exception $e) {
                return (object)array('error' => 'Riot\'s a little too busy, try again later (2)');
            }

            $data->summoner = current((array)json_decode($result));



            /* API Query - Champion Mastery Endpoint
            ===================================== */  
            try {
                $string = file_get_contents("../../data/champions.json");
                $championsData = json_decode($string, true);
                $string2 = file_get_contents("../../data/positions.json");
                $positionsData = json_decode($string2, true);
            }
            catch (Exception $e) {
                return (object)array('error' => 'The server is a little too busy, try again later (8)');
            }
            
            $url_masteryScore = 'https://' . $region . '.api.pvp.net/championmastery/location/'.$altRegion.'/player/' . $data->summoner->id . '/score?api_key='. $this->riotAPIKey;
            $url_championMasteries = 'https://' . $region . '.api.pvp.net/championmastery/location/'.$altRegion.'/player/' . $data->summoner->id . '/champions?api_key='. $this->riotAPIKey;
            $url_stats = 'https://' . $region . '.api.pvp.net/api/lol/'. $region.'/v1.3/stats/by-summoner/' . $data->summoner->id . '/ranked?season=SEASON2016&api_key='. $this->riotAPIKey;

            try{
                $data->summoner->masteryScore = file_get_contents($url_masteryScore);
                $championMasteries = json_decode(file_get_contents($url_championMasteries), true);
                $statsData = json_decode(file_get_contents($url_stats), true);
                $stats = $statsData['champions'];
            }
            catch (Exception $e) {
                return (object)array('error' => 'Riot\'s a little too busy, try again later (5)');
            }
            
            $data->chestsEarned = 0;
            $champions = new stdClass();
            $champions->unplayed = array();
            $champions->perspective = array();
            $champions->earned = array();
                
            foreach($championsData['data'] as $name => $info) {
                $champion = new stdClass();
                $champion->id = $info['id'];
                $champion->name = $name;
                $champion->image = $info['image']['full'];
                $champion->highestGrade = 'None';
                
                // Initialize the score categories, assume they would get "C" on a champ without data
                $points = new stdClass();
                $points->stats_winPercent = 0; 
                $points->stats_multiKills = 0; 
                $points->mastery_highestGrade = 4; 
                $points->mastery_level = 0; 

                // Default to this and overwrite
                $type = 'unplayed';  
                
                // Find position data
                foreach($positionsData as $positionName => $championPositionData) {   
                    if($name == $positionName) {
                        $champion->positions = $championPositionData["positions"];
                        break;
                    }
                }
                
                // Find the champion in the user's stats
                foreach($stats as $championStats) {                                                             
                    if($championStats['id'] == $info['id']) {  
                        // They have played this champion
                        $type = 'perspective';
                        
                        // Get points for win percent
                        // Linear interpolation, >70% = 10 pts, <30% = 0 pts
                        $winPercent = 100 *  $championStats['stats']['totalSessionsWon']/($championStats['stats']['totalSessionsLost']+$championStats['stats']['totalSessionsWon']);
                        $points->stats_winPercent = round(min(max(10*($winPercent-30)/(70-30),0),10));
                        
                        // Get points for multikills
                        // Cap each at 3 so max total points is 9
                        $gamesPlayed = $championStats['stats']['totalSessionsWon'] + $championStats['stats']['totalSessionsLost'];
                        $tripleKill = min($championStats['stats']['totalTripleKills']/$gamesPlayed*5/0.15,3);
                        $quadraKill = min($championStats['stats']['totalQuadraKills']/$gamesPlayed*5/0.25,3);
                        $pentaKill = min($championStats['stats']['totalPentaKills']/$gamesPlayed*5/0.5,3);
                        $points->stats_multiKills = round($tripleKill + $quadraKill + $pentaKill);
                        
                        $champion->multi = $points->stats_multiKills ;
                        // Stop going through champions as we have found the one
                        break;
                    }
                }
                
                // Check if they have champion mastery data                
                foreach($championMasteries as $mastery) {
                    if($mastery['championId'] == $info['id']) {
                        // They have played this champion
                        $type = 'perspective';
                        
                        if($mastery['chestGranted'] == true) {
                            $type = 'earned';
                            $data->chestsEarned += 1;
                        }
                        
                        if(array_key_exists('highestGrade', $mastery)) {
                            $champion->highestGrade = $mastery['highestGrade'];                       
                            switch($mastery['highestGrade']) {                                          
                                case 'S+': $points->mastery_highestGrade = 14; break;
                                case 'S': $points->mastery_highestGrade = 13; break;
                                case 'S-': $points->mastery_highestGrade = 12; break;
                                case 'A+': $points->mastery_highestGrade = 11; break;
                                case 'A': $points->mastery_highestGrade = 10; break;
                                case 'A-': $points->mastery_highestGrade = 9; break;
                                case 'B+': $points->mastery_highestGrade = 8; break;
                                case 'B': $points->mastery_highestGrade = 7; break;
                                case 'B-': $points->mastery_highestGrade = 6; break;
                                case 'C+': $points->mastery_highestGrade = 5; break;
                                case 'C': $points->mastery_highestGrade = 4; break;
                                case 'C-': $points->mastery_highestGrade = 3; break;
                                case 'D+': $points->mastery_highestGrade = 2; break;
                                case 'D': $points->mastery_highestGrade = 1; break;
                                case 'D-': $points->mastery_highestGrade = 0; break;
                            }
                        }
                        
                        $champion->championLevel = $mastery['championLevel'];
                        $points->mastery_level = 2 * $mastery['championLevel'];
                        
                        // Stop going through champions as we have found the one
                        break;
                    }
                }
                
                // Sum up the points
                $champion->championScore = 0;
                foreach ((array)$points as $v) {
                    $champion->championScore += intval($v);
                }
                $champion->championCarryScore = $points->stats_winPercent * 3;
                
                switch($type) {
                    case 'unplayed'; array_push($champions->unplayed,$champion); break;
                    case 'perspective'; array_push($champions->perspective,$champion); break;
                    case 'earned'; array_push($champions->earned,$champion); break;
                }
                
            }
            
            // Max chests is based on hextech's release on March 15, starting with 4 boxes 
            // available, and going to an estimated season end of Jan 20 (like in 2015)
            $data->maxChests = 48;                  
            
            // On track chests are based on how many chests you've earned to date vs the max
            // you could earn to date, extrapolated to the end of the season
            $season_start = date_create("2016-03-15");
            $season_end = date_create("2017-01-20");
            $today = date_create();
            $data->onTrack = min(round($data->chestsEarned/((date_diff($season_start, $today)->days)/date_diff($season_start,$season_end)->days*$data->maxChests)*$data->maxChests),$data->maxChests);
            
            $data->champions = $champions;
            $data->percent = round(100 * $data->chestsEarned / $data->maxChests);

            /* Final cleanup
            ===================================== */   
            restore_error_handler();
            return $data;
            
        } else {
            return "Only accepts GET requests";
        }
     }
    
    private function get_http_response_code($url) {
        $headers = get_headers($url);
        return substr($headers[0], 9, 3);
    }
    
 }




?>