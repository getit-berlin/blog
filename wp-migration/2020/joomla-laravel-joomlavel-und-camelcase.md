## Joomla, Laravel, JoomLavel und camelCase

von gRoot

am 2020-09-16

in IT-Services, Entwicklung

Ein kleiner Überblick:

- snake_case / lowercase => C / C++, (my favorite)
- camelCase
- PascalCase
- kebab-case

| **Framework** | **Klassen** | **Methoden** | **Attribute** |
| --- | --- | --- | --- |
| Laravel | PascalCase | camelCase | camelCase |
| WordPress CMS | | snake_case | |
| Joomla CMS | PascalCase | camelCase | camelCase |

_Übersicht der Frameworks und der Schreibstile_

In unserem JoomLavel Projekt halten wir uns an die Joomla und Laravel Stile. Es gibt bei Laravel natürlich folgendes zu bedenken:

> By convention, the "snake case", plural name of the class will be used as the table name unless another name is explicitly specified.
>
> — https://laravel.com/docs/7.x/eloquent#eloquent-model-conventions

Somit wird der Tabellenname in der Datenbank im snake_case im **plural** angegeben. Das entsprechde Model im Laravelcode aber im PascalCase

Im Eloquent Beispiel sieht das so aus:

```
DB Tabelle: my_flights, Model: MyFlight
```

Im Frontend in HTML sollten Attribute im kebap-case geschrieben werden. Wenn wir z.B. das data-attribute nutzen.

```
data-user-id="121"
```

tag camelCase eloquent joomla joomlavel laravel lumen php