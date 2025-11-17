<?php

/*
 * Using the custom library tFPDF modded from FPDF and the FPDI library to import external files and write on top of them. 
 * 
 * */


require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader;
use setasign\Fpdi\Tfpdf;


class BadgeBuilder {

    //Folder
    public static $badge_folder = ABSPATH . 'wp-content/plugins/eventer/badges';
    public static $badge_folder_url = '/wp-content/plugins/eventer/badges';

    // Outputs
    public $id;
    public $badge_output;
    public $errors;

    // Inputs
    private $is_single_badge;
    private $registration_id;
    private $first_name;
    private $last_name;
    private $role;
    private $company;

    // Predefined coordinates for badge fields
    private $page_1_x_coord;
    private $page_1_y_coord;
    private $page_2_x_coord;
    private $page_2_y_coord;
    private $template_base;

    private $name_format;
    private $job_format;
    private $company_format;

    // Computed props
    public $badge_filename;
    public $template_file;
    public $barcode;
    


    function __construct($id, $single) {
        $this->is_single_badge = $single;
        $this->registration_id = $id;
        $this->id = $id;
        $this->errors = array();
        $this->badge_output = array();

        self::make_the_badge_folder();

        $this->get_registration_data();

        $this->set_up_badge_vars();

        $this->grab_badge_formatting();


        if(empty($this->errors)) {

            $this->set_badge_url();
            $this->set_badge_filename();
            // Create barcode for badge
            $this->create_barcode();
            $this->build_badge();

        }

        if($this->is_single_badge === true) {
            $this->store_badge();
        }

        $this->set_database_record_to_printed();

    }



    private function grab_badge_formatting() {

        $this->name_format = unserialize(get_option('badge_name_format'));
        $this->job_format = unserialize(get_option('badge_job_format'));
        $this->company_format = unserialize(get_option('badge_company_format'));

    }


    private function set_badge_filename() {

        $initial = substr($this->first_name,0,1);
        $last = preg_replace('/[^A-Za-z0-9\-]/', '', $this->last_name);
        $comp = preg_replace('/[^A-Za-z0-9\-]/', '', $this->company);
        $t = time();
        $time = substr($t,4, 5);

        $this->badge_filename = $initial . "_" . $last . "_" . $comp . "_" . $time . ".pdf";

    }


    private function set_badge_url() {

        $template_url = parse_url($this->template_base);
        $path_fix = ltrim($template_url['path'], '/');
        
        if(isset($template_url['query']) && !empty($template_url['query'])) {
    	    $this->template_file = ABSPATH . $path_fix . $template_url['query'];
	    } else {
		    $this->template_file = ABSPATH . $path_fix;
	    }

    }


    private function set_up_badge_vars() {

        if(empty(get_option('badge_x'))) {
            $this->errors[] = "Page 1 x co-ordinate not set";
        } else {
            $this->page_1_x_coord = get_option('badge_x');
        }
        if(empty(get_option('badge_x_p2'))) {
            $this->errors[] = "Page 2 x co-ordinate not set";
        } else {
            $this->page_2_x_coord = get_option('badge_x_p2');
        }
        if(empty(get_option('badge_y'))) {
            $this->errors[] = "Page 1 y co-ordinate not set";
        } else {
            $this->page_1_y_coord = get_option('badge_y');
        }
        if(empty(get_option('badge_y_p2'))) {
            $this->errors[] = "Page 2 y co-ordinate not set";
        } else {
            $this->page_2_y_coord =  get_option('badge_y_p2');
        }
        if(empty(get_option('badge_template'))) {
            $this->errors[] = "Cannot find template file for badge";
        } else {
            $this->template_base = get_option('badge_template');
        }

    }


    private function get_registration_data() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'registrations';
        $sql = "SELECT * FROM $table_name WHERE id = $this->registration_id";
        $result = $wpdb->get_results($sql);

        if (!empty($result)) {
            $this->first_name = strip_tags(trim($result[0]->name));
            $this->last_name = strip_tags(trim($result[0]->surname));
            $this->role = strip_tags(trim($result[0]->role));
            $this->company = strip_tags(trim($result[0]->company));
        } else {
            $this->errors[] = "Could not find registration data for this visitor";
        }

    }


	
    private function build_badge() {

        // Instantiate
		$pdf = new Tfpdf\Fpdi();
		$pdf->AddPage(); 
		
		// Add custom fonts in UTF-8
		define('FPDF_FONTPATH', plugin_dir_path( __FILE__ ) . '/vendor/setasign/tfpdf/font/');
		$pdf->AddFont('NotoSans-Regular','','NotoSans-Regular.ttf', true);
		$pdf->AddFont('NotoSans-Bold','B','NotoSans-Bold.ttf', true);
		$pdf->AddFont('NotoSans-Black','B','NotoSans-Black.ttf', true);
		        
        // Create page
        $pdf->setSourceFile($this->template_file); 
        $tplIdx = $pdf->importPage(1); 
        $pdf->useTemplate($tplIdx, 0, 0); 

        // Stored colors
        $black = array(0,0,0);
        $dark = array(35,35,35);
        $luxury = array(210, 189, 142);
        $dagora_blue = array(0,132,203);
        $dagora_green = array(140, 198, 63);
        $dagora_purple = array(131, 87, 164);

        // Split name field?
        if(get_option('badge_namebreak')) {
            $name_field = $this->first_name . "\n" . $this->last_name;
        } else {
            $name_field = $this->first_name . " " . $this->last_name;
        }

        $name = array(
            'width' => 85,
            'height' => 10,
            'color' => $black,
            'font_size' => 15,
            'caps' => true,
            'text' => $name_field,
            'reset' => true,
            'align' => 'L',
        );
        $job = array(
            'width' => 85,
            'height' => 8,
            'color' => $black,
            'font_size' => 10,
            'caps' => false,
            'text' => $this->role,
            'reset' => false,
            'align' => 'L',
        );
        $company = array(
            'width' => 85,
            'height' => 8,
            'color' => $dagora_green,
            'font_size' => 12,
            'caps' => true,
            'text' => $this->company,
            'reset' => false,
            'align' => 'L',
        );  


        // See if we've already saved out some formatting options
        if(!empty($this->name_format)) {
            if(!empty($this->name_format['color'])) {

                switch ($this->name_format['color']) {
                    case 'black':
                        $name['color'] = $black;
                        break;
                    
                    case 'dark':
                        $name['color'] = $dark;
                        break;

                    case 'luxury':
                        $name['color'] = $luxury;
                        break;
                    
                    case 'blue':
                        $name['color'] = $dagora_blue;
                        break;

                    case 'green':
                        $name['color'] = $dagora_green;
                        break;
                    
                    case 'purple':
                        $name['color'] = $dagora_purple;
                        break;
                    
                    default:
                        $name['color'] = $name['color'];
                        break;
                }

            } 
            if(!empty($this->name_format['fontsize'])) {
                $name['font_size'] = $this->name_format['fontsize'];
            }
            if(!empty($this->name_format['caps'])) {                
                if($this->name_format['caps'] == 'yes') $name['caps'] = true;
                if($this->name_format['caps'] == 'no') $name['caps'] = false;
            }
            if(!empty($this->name_format['align'])) {
                $name['align'] = $this->name_format['align'];
            }
        }

        if(!empty($this->job_format)) {
            if(!empty($this->job_format['color'])) {

                switch ($this->job_format['color']) {
                    case 'black':
                        $job['color'] = $black;
                        break;
                    
                    case 'dark':
                        $job['color'] = $dark;
                        break;

                    case 'luxury':
                        $job['color'] = $luxury;
                        break;
                    
                    case 'blue':
                        $job['color'] = $dagora_blue;
                        break;

                    case 'green':
                        $job['color'] = $dagora_green;
                        break;
                    
                    case 'purple':
                        $job['color'] = $dagora_purple;
                        break;
                    
                    default:
                        $job['color'] = $job['color'];
                        break;
                }
            } 
            if(!empty($this->job_format['fontsize'])) {
                $job['font_size'] = $this->job_format['fontsize'];
            }
            if(!empty($this->job_format['caps'])) {                
                if($this->job_format['caps'] == 'yes') $job['caps'] = true;
                if($this->job_format['caps'] == 'no') $job['caps'] = false;
            }
            if(!empty($this->job_format['align'])) {
                $job['align'] = $this->job_format['align'];
            }
        }

        if(!empty($this->company_format)) {
            if(!empty($this->company_format['color'])) {

                switch ($this->company_format['color']) {
                    case 'black':
                        $company['color'] = $black;
                        break;
                    
                    case 'dark':
                        $company['color'] = $dark;
                        break;

                    case 'luxury':
                        $company['color'] = $luxury;
                        break;
                    
                    case 'blue':
                        $company['color'] = $dagora_blue;
                        break;

                    case 'green':
                        $company['color'] = $dagora_green;
                        break;
                    
                    case 'purple':
                        $company['color'] = $dagora_purple;
                        break;
                    
                    default:
                        $company['color'] = $company['color'];
                        break;
                }

            } 
            if(!empty($this->company_format['fontsize'])) {
                $company['font_size'] = $this->company_format['fontsize'];
            }
            if(!empty($this->company_format['caps'])) {                
                if($this->company_format['caps'] == 'yes') {
					$company['caps'] = true;
				} else {
					$company['caps'] = false;
				}  
            }
            if(!empty($this->company_format['align'])) {
                $company['align'] = $this->company_format['align'];
            }
        }



        // Write name on p1
        $this->print_badge_text($name, $this->page_1_x_coord, $this->page_1_y_coord, $pdf);

        // Write job on p1
        $this->print_badge_text($job, $this->page_1_x_coord, $this->page_1_y_coord, $pdf);

        $this->do_gap(20, $pdf);

        // Write company on p1
        $this->print_badge_text($company, $this->page_1_x_coord, $this->page_1_y_coord, $pdf);


        // Write name on p2
        $this->print_badge_text($name, $this->page_2_x_coord, $this->page_2_y_coord, $pdf);

        // Write job on p2
        $this->print_badge_text($job, $this->page_2_x_coord, $this->page_2_y_coord, $pdf);

        $this->do_gap(20, $pdf);

        // Write company on p2
        $this->print_badge_text($company, $this->page_2_x_coord, $this->page_2_y_coord, $pdf);

        $this->paste_in_badge_barcode($pdf);


        // Storing URL of file to download in the object output var
        if($this->is_single_badge === true) {
            $pdf->Output(self::$badge_folder . '/' . $this->badge_filename, 'F');
            $this->badge_output = site_url() . self::$badge_folder_url . "/" . $this->badge_filename;
        } else {
            if (!file_exists(self::$badge_folder . "/temp/")) {
                mkdir(self::$badge_folder . "/temp/");
            }
            $pdf->Output(self::$badge_folder . '/temp/' . $this->badge_filename, 'F');
        }
    }

    
    private function create_barcode() {

        // Create barcode for badge
        $barcode = new \Com\Tecnick\Barcode\Barcode();
        $result = $barcode->getBarcodeObj('C128A', $this->registration_id, -3, -30, 'black', array(0, 0, 0, 0));
        //$this->barcode = <p><img alt=\"Embedded Image\" src=\"data:image/png;base64," . base64_encode($result->getPngData()) . "\" /></p>
        $this->barcode = base64_encode($result->getPngData());
        //$this->barcode = $result->getPngData();

    }


    private function paste_in_badge_barcode($pdf) {

        $pdf->Image('data:image/png;base64,' . $this->barcode, 51, 134, 0, 0, 'PNG');

        $pdf->Image('data:image/png;base64,' . $this->barcode, 155, 134, 0, 0, 'PNG');

    }


    private function print_badge_text($opts, $x, $y, $pdf) {
		
        if($opts['caps'] === true) {
            $opts['text'] = mb_strtoupper($opts['text'], 'UTF-8');
			//$opts['text'] = strtoupper($opts['text']);
        }
        
        $pdf->SetTextColor($opts['color'][0],$opts['color'][1],$opts['color'][2]);
		
		$pdf->SetFont('NotoSans-Black', 'B', $opts['font_size']); /****** Change font here! ****/

        // Only usually reset both x and y for name field, i.e. first element of new page, else just x
        if($opts['reset'] === true) {
            $pdf->SetXY($x, $y);
        } else {
            $pdf->SetX($x);
        }

        $pdf->Multicell($opts['width'], $opts['height'], $opts['text'], 0, $opts['align'], false); 
            
    }



    public static function kill_the_old_badges($dir = null) {

        if($dir === null) {

            unlink(self::$badge_folder . "/all_badges.zip");
        
        } else {
        
            foreach(glob($dir . '/*') as $file) {
                if(is_dir($file)) {
                    
                    self::kill_the_old_badges($file);
                
                } else { 

                    unlink($file);

                }
            }
        }

        rmdir($dir);

    }

    public static function make_the_badge_folder() {
        if (!file_exists(self::$badge_folder . '/')) {
            mkdir(self::$badge_folder . '/');
        }
    }

    public static function zipOutput() {
        // We have a hard-coded folder where we store output badges.
        // On command, we can simply zip up that folder and return the URL to access it.
        // We do not need to know files or anything, just the folder name.
       
        $the_folder = self::$badge_folder . "/temp/";
        $zip_file_name = self::$badge_folder . '/all_badges.zip';
        
        $za = new FlxZipArchive;
        $res = $za->open($zip_file_name, ZipArchive::CREATE);
        if($res === TRUE) {
            $za->addDir($the_folder, basename($the_folder));
            $za->close();

            self::kill_the_old_badges(self::$badge_folder . "/temp/");

            //return $zip_file_name; TESTING WITH URL NOT FILE
            return site_url() . self::$badge_folder_url . '/all_badges.zip';
        } else {
            return 'Could not create a zip archive';
        }


    }


    private function store_badge() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'registrations';
        $sql = "UPDATE $table_name SET badge_link = '$this->badge_output' WHERE id = $this->registration_id";
        $wpdb->query($sql);


    }


    private function set_database_record_to_printed() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'registrations';
        $sql = "UPDATE $table_name SET printed = 1 WHERE id = $this->registration_id";
        $wpdb->query($sql);

    }


    	private function do_gap($gap_size, $pdf) {
		$current_y = $pdf->GetY();
		$pdf->SetY($current_y + $gap_size);
	}


}

class FlxZipArchive extends ZipArchive {
    public function addDir($location, $name) {
          $this->addEmptyDir($name);
          $this->addDirDo($location, $name);
    } 
    private function addDirDo($location, $name) {
       $name .= '/';
       $location .= '/';
       $dir = opendir ($location);
       while ($file = readdir($dir)) {
           if ($file == '.' || $file == '..') continue;
           $do = (filetype( $location . $file) == 'dir') ? 'addDir' : 'addFile';
           $this->$do($location . $file, $name . $file);
       }
    } 
   }
