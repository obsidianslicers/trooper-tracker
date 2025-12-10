<?php

declare(strict_types=1);

namespace App\Console\Commands\Synchronizers;

use Illuminate\Console\Command;

/**
 * Artisan command to calculate and store trooper achievements based on their event history.
 *
 * This command aggregates event data for each trooper, such as total troops,
 * volunteer hours, and funds raised, and then updates their corresponding
 * achievements in the database.
 */
class MandalorianSynchronizer extends BaseSynchronizer
{
    public function __construct(Command $command)
    {
        parent::__construct($command, 'Mandalorian Mercs');
    }

    public function run(): void
    {
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:synchronize-organizations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Organizations.';


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->theLegion();
        $this->theRebelLegion();
        $this->theMercs();
        $this->theDroidBuilders();
        $this->theDarkEmpire();
        $this->theSaberGuild();
    }
    private function theLegion(): void
    {
        // TODO: implement
    }

    private function theRebelLegion(): void
    {
        /*<?php

/**
 * This file is used for scraping Rebel Legion data.
 * 
 * This should be run weekly by a cronjob.
 *
 * @author  Matthew Drennan
 *
 *

// Include config
include(dirname(__DIR__) . '/../../config.php');

// Get Simple PHP DOM Tool - just a note, for this code to work, $stripRN must be false in tool
include(dirname(__DIR__) . '/simple_html_dom.php');

// Check date time for sync
$statement = $conn->prepare("SELECT syncdaterebels FROM settings");
$statement->execute();
$statement->bind_result($syncdaterebels);
$statement->fetch();
$statement->close();

// Compare dates
if(strtotime($syncdaterebels) >= strtotime("-7 day"))
{
    // Prevent script from continuing
    die("Already updated recently.");
}

// Purge rebel troopers
$statement = $conn->prepare("DELETE FROM rebel_troopers");
$statement->execute();

// Purge rebel costumes
$statement = $conn->prepare("DELETE FROM rebel_costumes");
$statement->execute();

// Costume image array (duplicate check)
$costumeImagesG = array();

// Loop through all Rebels
$statement = $conn->prepare("SELECT * FROM troopers WHERE pRebel > 0");
$statement->execute();

if ($result = $statement->get_result())
{
    while ($db = mysqli_fetch_object($result))
    {
        // Search ID for profile
        $html = file_get_html('https://www.forum.rebellegion.com/forum/profile.php?mode=viewprofile&u=' . urlencode($db->rebelforum));

        // If cannot get page, go to next trooper
        if(!$html) { continue; }

        // Set Rebel ID
        $rebelID = $db->rebelforum;
        $rebelName = "";
        $rebelForum = $db->rebelforum;

        // Costume name array
        $costumeNames = array();

        // Costume image array
        $costumeImages = array();

        // Loop through costume images on profile
        try {
            foreach($html->find('img[height=125]') as $r)
            {
                // Set should add to prevent duplicates
                $addTo = true;

                // Check to see if exists in duplicate array
                if(in_array(str_replace("sm", "-A", $r->src), $costumeImagesG))
                {
                    $addTo = false;
                }

                // Check to see if we can add (duplicates)
                if($addTo)
                {
                    // Push to array
                    array_push($costumeImages, str_replace("sm", "-A", $r->src));

                    // Push to array (to check for duplicates)
                    array_push($costumeImagesG, str_replace("sm", "-A", $r->src));

                    // Get costume names
                    foreach($r->parent->parent->parent->find('span[class=gen] a') as $s)
                    {
                        array_push($costumeNames, $s->innertext);
                    }
                }
            }
        } catch(Exception $e) {
            // Error
            echo 'Error! <br />';
            continue;
        }

        // Start i count
        $cc = 0;

        // Loop through created arrays
        foreach($costumeNames as $c)
        {
            // Query
            $statement = $conn->prepare("INSERT INTO rebel_costumes (rebelid, costumename, costumeimage) VALUES (?, ?, ?)");
            $statement->bind_param("sss", $rebelID, $costumeNames[$cc], $costumeImages[$cc]);
            $statement->execute();

            // Increment
            $cc++;
        }

        // Get Rebel name
        try {
            foreach($html->find('td[class=catRight] span') as $r)
            {
                $rebelName = preg_match( '!\(([^\)]+)\)!', $r->innertext, $match);
                $rebelName = $match[1];
            }
        } catch(Exception $e) {
            // Error
            echo 'Error! <br />';
            continue;
        }

        if(is_null($rebelName)) { $rebelName = ""; }

        // Query
        $statement = $conn->prepare("INSERT INTO rebel_troopers (rebelid, name, rebelforum) VALUES (?, ?, ?)");
        $statement->bind_param("sss", $rebelID, $rebelName, $rebelForum);
        $statement->execute();
    }
}

// Pull extra data from spreadsheet
$values = getSheet("1I3FuS_uPg2nuC80PEA6tKYaVBd1Qh1allTOdVz3M6x0", "Troopers");

// Set up count
$i = 0;

foreach($values as $value)
{
    // If not first
    if($i != 0)
    {
        // Query
        $statement = $conn->prepare("INSERT INTO rebel_troopers (rebelid, name, rebelforum) VALUES (?, ?, ?)");
        $statement->bind_param("sss", $value[0], $value[1], $value[2]);
        $statement->execute();
    }

    // Increment
    $i++;
}

// Pull extra data from spreadsheet
$values = getSheet("1I3FuS_uPg2nuC80PEA6tKYaVBd1Qh1allTOdVz3M6x0", "Costumes");

// Set up count
$i = 0;

foreach($values as $value)
{
    // If not first
    if($i != 0)
    {
        // Insert into database
        $statement = $conn->prepare("INSERT INTO rebel_costumes (rebelid, costumename, costumeimage) VALUES (?, ?, ?)");
        $statement->bind_param("sss", $value[0], $value[1], $value[2]);
        $statement->execute();
    }

    // Increment
    $i++;
}

echo '
COMPLETE!';

// Update date time for last sync
$statement = $conn->prepare("UPDATE settings SET syncdaterebels = NOW()");
$statement->execute();

?> */
    }

    private function theMercs(): void
    {
        // TODO: implement
    }

    private function theDroidBuilders(): void
    {
        /*<?php

/**
 * This file is used for scraping Droid Builder data.
 * 
 * This should be run weekly by a cronjob.
 *
 * @author  Matthew Drennan
 *
 *

// Include config
include(dirname(__DIR__) . '/../../config.php');

// Purge Droids
$conn->query("DELETE FROM droid_troopers");

// Pull extra data from spreadsheet
$values = getSheet("195NT1crFYL_ECVyzoaD2F1QXGW5WxlnBDfDaLVtM87Y", "Sheet1");

// Set up count
$i = 0;

foreach($values as $value)
{
    // If not first
    if($i != 0)
    {
        $value[0] = cleanInput($value[0]);
        $value[1] = cleanInput($value[1]);
        $value[2] = cleanInput($value[2]);

        // Insert into database
        $statement = $conn->prepare("INSERT INTO droid_troopers (forum_id, droidname, imageurl) VALUES (?, ?, ?)");
        $statement->bind_param("sss", $value[0], $value[1], $value[2]);
        $statement->execute();
    }

    // Increment
    $i++;
}

?> */
    }

    private function theDarkEmpire(): void
    {
        // TODO: implement
    }

    private function theSaberGuild(): void
    {
        /*<?php

/**
 * This file is used for scraping Saber Guild data.
 * 
 * This should be run weekly by a cronjob.
 *
 * @author  Matthew Drennan
 *
 *

// Include config
include(dirname(__DIR__) . '/../../config.php');

// Get Simple PHP DOM Tool - just a note, for this code to work, $stripRN must be false in tool
include(dirname(__DIR__) . '/../../tool/dom/simple_html_dom.php');

// Purge SG troopers
$statement = $conn->prepare("DELETE FROM sg_troopers");
$statement->execute();

// Pull extra data from spreadsheet
$values = getSheet("1PcveycMujakkKeG2m4y8iFunrFbo2KVpQJ00GyPI3b8", "Sheet1");


// Set up count
$i = 0;

foreach($values as $value)
{
    // If not first
    if($i != 0)
    {
        // Set up image
        $image = $value[4];

        // Convert Google Drive link
        if (strpos($image, "view?usp=drivesdk") !== false)
        {
            $image = explode("/", $image);
            $image = "https://drive.google.com/uc?id=" . $image[5] . "";
        }

        $value[2] = cleanInput($value[2]);
        $value[0] = cleanInput($value[0]);
        $value[1] = cleanInput($value[1]);
        $value[3] = cleanInput($value[3]);
        $image = cleanInput($image);

        // Insert into database
        $statement = $conn->prepare("INSERT INTO sg_troopers (sgid, name, image, ranktitle, costumename, link) VALUES (?, ?, ?, ?, ?, '')");
        $statement->bind_param("isssss", $value[2], $value[0], $image, $value[1], $value[3]);
        $statement->execute();


    }

    // Increment
    $i++;
}

?> */
    }

}
