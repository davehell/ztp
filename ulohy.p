/*
	Vypsání všech úloh ve stromu. Výstup ve formátu JS pole.
	Využití v aplikaci ZTP - pro našeptávání názvu úloh.

	ukázka výstupu:
	[
	"Trendy měřených dat - Skupina uzlů",
	"Trendy měřených dat - Více zvolených uzlů",
	"Trendy měřených dat - Základní přehled",
	...
	"Evidence paliv - Sklady paliv - Přehledy",
	""
	]
*/
{src/web/method/cgidefs.i}
{i-strom.i   &tabl = "wf" }

OUTPUT-CONTENT-TYPE ("application/json").

RUN f-strom.p("energis", "cs", "dhe", 0, "", 3, INPUT-OUTPUT TABLE wf).

def var nadpisy as char extent 3 no-undo.

{&OUT} "[\n".

for each wf:
	if wf.uroven = 1 then do:
		assign nadpisy[1] = substr(wf.napis, 1, 1) + substr(lc(wf.napis), 2)
					 nadpisy[2] = ""
					 nadpisy[3] = "".
	end.
	else if wf.uroven = 2 then do:
		assign nadpisy[2] = wf.napis
					 nadpisy[3] = "".
	end.
	if wf.uroven = 3 then do:
		assign nadpisy[3] = wf.napis.
	end.

	/* vynechat položky: */
	if wf.ikona[1] <> 0 then next. /* které nemají ikonu složky */
	if nadpisy[1] begins "MONIT" then next. /* monitorování */
	if nadpisy[1] begins "OBL" then next. /* oblíbené */
	if nadpisy[1] begins "MOJE" then next. /* moje stránky */
	if nadpisy[1] begins "DOKUMENTACE" then next. /* dokumentace */
	if wf.uroven = 1 then next. /* položky z první úrovně stromu */

	{&OUT} '"' + nadpisy[1].
	{&OUT} " - " + nadpisy[2].
	if nadpisy[3] <> "" then {&OUT} " - " + nadpisy[3].
	{&OUT} '",' + "\n".
end.

{&OUT} '""' + "\n".
{&OUT} "]\n".
