<?php


namespace App\Console\Commands;
require 'vendor/autoload.php';
use App\Reference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use PhpScience\TextRank\Tool\StopWords\English;
use PhpScience\TextRank\TextRankFacade;
use DOMDocument;

class LoadReferencesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'references:load';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'load reference data from json file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $path = storage_path( ) ."/references.json";
        $file = trim(file_get_contents($path), "\xEF\xBB\xBF");
        $json = json_decode($file, true);
        $this->info('Loading references from json. This might take a while...');
        
        
        foreach ($json as $item) {
            $this->info("\n write: ");
            //$this->info($item['content']);
            //$keycontent = Keys($item['content']);
            //$this->info($keycontent);
            $url = $item['content'];
            $listanegra = array('a', 'ante', 'bajo', 'con', 'contra', 'de', 'desde', 'mediante', 'durante', 'hasta', 'hacia', 'para', 'por', 'que', 'qué', 'cuán', 'cuan', 'los', 'las', 'una', 'unos', 'unas', 'donde', 'dónde', 'como', 'cómo', 'cuando', 'porque', 'por', 'para', 'según', 'sin', 'tras', 'con', 'mas', 'más', 'pero', 'del');

            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTMLFile($url);
            $webhtml = $doc->getElementsByTagName('p');
            $webhtml = $webhtml->item(0)->nodeValue;
            $webhtml = strip_tags($webhtml);
            $webhtml = explode(' ', $webhtml);

            $palabras = array();
            foreach($webhtml as $word)
            {
                $word = strtolower(trim($word, ' .,!?()')); // remove trailing special chars and spaces
                $api = new TextRankFacade();
                $stopWords = new English();
                $api->setStopWords($stopWords);
                $word = implode($api->summarizeTextBasic($word));
                if (!in_array($word, $listanegra))
                {
                    $palabras[] = $word;
                }
            }
            $frq = array_count_values($palabras);
            asort($frq);
            
            $keycontent = implode(' ', array_keys($frq));

            $referenceData = ['content'=>$item['content'], 'keycontent'=>$keycontent]; 
            Reference::create($referenceData);
            $this->output->write('.');
        }
        
        $this->info("\n loading complete");
    }
    
}
