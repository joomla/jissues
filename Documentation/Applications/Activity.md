## Activity Application

### Purpose

Displays activity charts related to tracker activity for supported projects.

### Functionality

* Web pages displaying different types of activity charts
* AJAX handlers for dynamic chart updates

### Formatted Dates

Some charts use localised date strings based on PHP's `ext/intl`.  Below are the references for the currently supported languages:
 
#### User Activity Chart

These formats are used in the title when a custom date range is used:

Language Code | Format
------------- | -------------
ca-ES         | d MMMM 'de' y
da-DK         | d. MMMM y
de-DE         | d. MMMM y
en-GB         | d MMMM y
es-CO         | d 'de' MMMM 'de' y
es-ES         | d 'de' MMMM 'de' y
et-EE         | d. MMMM y
fr-CA         | d MMMM y
fr-FR         | d MMMM y
hu-HU         | y. MMMM d.
id-ID         | d MMMM y
it-IT         | d MMMM y
lv-LV         | y. 'gada' d. MMMM
nb-NO         | d. MMMM y
nl-BE         | d MMMM y
nl-NL         | d MMMM y
pl-PL         | d MMMM y
pt-BR         | d 'de' MMMM 'de' y
pt-PT         | d 'de' MMMM 'de' y
ro-RO         | d MMMM y
ru-RU         | d MMMM y 'г'.
sl-SI         | dd. MMMM y
zh-CN         | y年M月d日
