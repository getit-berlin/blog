Das Typo3 CMS verwendet Fluid als Template Engine. Diese Engine kann einfach auch in anderen PHP standalone Projekten verwendet werden. Warum sollte man das machen?
- Ermöglicht einen Migrationspfad zum Typo3 CMS
- Es ermöglicht das wiederverwenden von Fluid Templates in eigenen Projekten.
- Es ermöglicht eine sukzesive Einarbeitung in die Typo3 Entwicklung, da andere Komponenten wie Extbase ausgelassen werden.

Wie integriert man Typo3/Fluid in ein standalone Projekt ohne Typo3 CMS? Wiefolgt:

`composer require typo3fluid/fluid`  
`composer install`  

Nach dem einfügen des Autoloaders wird dem standalone Projekt nur noch das TempalateView Objekt hinzugefügt:

`use TYPO3Fluid\Fluid\View\TemplateView;`  

`view = new TemplateView;`

Es müssen noch verschiedene Pfade gesetzt werden. Außerdem sind bestimmte Directories notwenig:

`site_package`  
`└── Resources`  
`    ├── Private`  
`    │   ├── Language`  
`    │   ├── Layouts`  
`    │   │   └── Page`  
`    │   ├── Partials`  
`    │   │   └── Page`  
`    │   └── Templates`  
`    │       └── Page`  
`    └── Public`  
`        ├── Css`  
`        ├── Images`  
`        └── JavaScript`

Eine Php Beispiel-Klasse in dem Typo3/Fluid in einer standalone Umgebung integriert wird, ist der https://github.com/q-u-o-s-a/fallout-grabber/blob/master/app/Http/Controllers/AbstractController.php des Fallout-Grabbers.

Weitere Informationen findet man unter: Typo3 Docs: FluidTemplates https://docs.typo3.org/m/typo3/tutorial-sitepackage/main/en-us/FluidTemplates/Index.html
