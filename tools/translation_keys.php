<?php require_once __DIR__ . '/inc/__tools_header.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';


$devToolScriptName =  'TranslationKeys';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('devcommunitytools'.$devToolScriptName));

global $dolibarr_main_document_root_alt;


$modules = dol_dir_list($dolibarr_main_document_root_alt);
// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$moduleDirectory = GETPOST('moduleDirectory', 'alphanothtml');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$error = 0;

/*
 * Actions
 */
$translations = array();
$logManager = new devCommunityTools\LogManager();
if($action == 'createLangFile'){
    if(!empty($moduleDirectory)){
        __processFiles($dolibarr_main_document_root_alt.'/'.$moduleDirectory,$translations,$logManager);
    }
    else{
        $logManager->addError("Erreur dans la création du lang file");
    }
}


/*
 * View
 */



$help_url = '';
$page_name = $langs->trans("DevCommunityTools").' - '.$langs->trans($devToolScriptName);
$arrayofjs = array(
    'devcommunitytools/js/devtools.js'
);

$arrayofcss = array(
    'devcommunitytools/css/devtools.css'
);

llxHeader('', $page_name, $help_url, '', 0, 0, $arrayofjs, $arrayofcss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : dol_buildpath('/devcommunitytools/admin/tools.php', 1)).'">'.$langs->trans("BackToToolsList").'</a>';

print load_fiche_titre($page_name, $linkback, 'title_setup');
/*
 * Start Script View
 */
print '<form action="'.$_SERVER['PHP_SELF'].'">';

print '<fieldset >';

print '	<legend>'.$langs->trans('ModuleSelection').'</legend>';
print '<input type="hidden" name="action" value="createLangFile" />';
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<select name="moduleDirectory" placeholder="'.$langs->trans('SelectModule').'">';
foreach($modules as $moduleInfo){
    print "<option value='".$moduleInfo["name"]."'";
    if($moduleDirectory == $moduleInfo["name"]){
        print " selected ";
    }
    print ">";
    print $moduleInfo["name"];
    print "</option>";
}
print '<select/>';
print '<button type="submit">'.$langs->trans('Submit').'</button>';

print '</fieldset>';

print '</form>';

$content = "";
foreach($translations as $translation){
    $content .= $translation."="."\n";
}

print '<div id="codeBlock">
    <pre><code>';
print $content;
 print '</code></pre>
</div>';
$logManager->output(true);
$langs->trans("toto");
/*
 * End Script View
 */

// Page end
print dol_get_fiche_end();


require_once __DIR__.'/inc/__tools_footer.php';

/**
 * @param string $directory
 * @param devCommunityTools\LogManager $logManager
 * @return int
 */
function __createLangFile($directory, $logManager){
    global $db, $langs;

    $keysToAvoid = array('XXX');
    if (empty($directory) || !dol_is_dir($directory)) {
        $logManager->addError('Incorrect Module Path');
        return -1;
    }
//    $filePath = $directory . '/quicklang.lang';
//    $file = fopen($filePath, 'w');
    $translations = array();
    $files = glob($directory . "/*.php");
    foreach ($files as $phpFile) {
        $content = file_get_contents($phpFile);
        preg_match_all('/\$langs->trans\(["\'](.*?)["\']/', $content, $matches);
        preg_match_all('/\$langs->transnoentitiesnoconv\(["\'](.*?)["\']/', $content, $matchesNoConv);
        $linetranslations = array_merge($translations, $matches[1], $matchesNoConv[1]);
        foreach ($linetranslations as $translation) {
            if(empty($translation) || isset($langs->tab_translate[$translation]) || in_array($translation, $keysToAvoid) || in_array($translation, $translations)){
                continue;
            }
            else{
                $translations[] = $translation;
            }
        }
    }
    return $translations;

}


/**
 * @param string $directory
 * @param devCommunityTools\LogManager $logManager
 * @return int|array
 */
function __processFiles($directory, &$translations, $logManager)
{
    global $langs;

    $keysToAvoid = array('XXX');
    if (empty($directory) || !dol_is_dir($directory)) {
        $logManager->addError('Incorrect Module Path');
        return -1;
    }

    $files = glob($directory . "/*.php");

    foreach ($files as $phpFile) {
        $content = file_get_contents($phpFile);
        preg_match_all('/\$langs->trans\(["\'](.*?)["\']/', $content, $matches);
        preg_match_all('/\$langs->transnoentitiesnoconv\(["\'](.*?)["\']/', $content, $matchesNoConv);
        $linetranslations = array_merge($matches[1], $matchesNoConv[1]);
        foreach ($linetranslations as $translation) {
            if(empty($translation) ){
                continue;
            }
            if(isset($langs->tab_translate[$translation])){
                continue;
            }
            if(in_array($translation, $translations)){
                continue;
            }

            $translations[] = $translation;
        }
    }

    $subdirectories = glob($directory . '/*', GLOB_ONLYDIR);
    foreach ($subdirectories as $subdirectory) {
        __processFiles($subdirectory, $translations, $logManager);
    }
}