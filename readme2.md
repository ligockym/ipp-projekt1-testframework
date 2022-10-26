Implementační dokumentace k 2. úloze do IPP 2021/2022

Jméno a příjmení: Marián Ligocký

Login: xligoc04

## Testovací skript

Slúži na spustenie a vyhodnotenie testov slúžiacich na kontrolu analyzátora a interpreta. Testy môžu byť uložené v
zložkách a podzložkách. Skript dovoľuje spúšťať separátne jednotlivé súčasti, rovnako aj spustiť ich zreťazene.

### Načítavanie testov zo zložiek

Pre načítanie všetkých testov sú hľadané všetky súbory v zložke s príponou `.src`. Následne sa skontroluje či existujú
súbory s príponou `.in` a `.rc` a prípadne sa dogenerujú.

### Spúštanie testov

V prípade, že sa má spustiť iba jedna časť - buď analyzátor, alebo interpret, načíta sa súbor so zdrojovým súborom s
príponou `.src` a jeho obsah sa pošle na vstup testovaného skriptu. Porovnajú sa návratové hodnoty (referenčný v
súbore `.rc`), pokiaľ sú oba nula, tak sa vykoná porovnanie obsahu.

Pokiaľ sa majú spustiť oba skripty za sebou, tak sa zdrojový súbor najprv použije ako vstup pre analyzátor a výstup sa
uloží s príponou `.parser.out` a pustí sa ako vstup pre interpret (spolu s prípadným súborom s príponou `.in`, ktorý
obsahuje vstupy pre interpret). Až na konci sa porovnajú návratové kódy (referenčný kód je v súbore `.rc`) a v prípade
oboch nulových kódov sa porovná obsah. Interpret sa púšťa iba pokiaľ výstup z analyzátora bol úspešný, teda vrátil
nulový návratový kód.

### Porovnanie obsahu

Obsah testových súborov sa vykonáva pomocou utilitky `diff` a v prípade porovnávania XML súborov pomocou programu
jexamxml. 
