local-exam_authorization
========================

Plugin local para autorização para realização de provas

Moodle Provas
=============

O "Moodle Provas" é uma solução desenvolvida pela
Universidade Federal de Santa Catarina
com financiamenteo do programa Universidade Aberta do Brasil (UAB)
para a realização de provas seguras nos pólos utilizando
o Moodle através da internet.

Além deste plugin, mais dois plugins compõem o pacote do Moodle Provas:

* local-exam_remote: Plugin que cria os webservices necessários no Moodle de origem
* block-exam_actions : Bloco que serve de interface para as ações sobre as provas

Foi desenvolvido também um "CD de Provas", derivado do Ubuntu, para
restringir o acesso aos recursos dos computadores utilizados
para realização da provas.

No endereço abaixo você pode acessar um tutorial sobre a
arquitetura do Moodle Provas:

    https://gitlab.setic.ufsc.br/moodle-provas/blocks-exam_actions/wikis/home

Download
========

Este plugin está disponível no seguinte endereço:

    https://gitlab.setic.ufsc.br/moodle-provas/local-exam_authorization

Os outros plugins podem ser encontrados em:

    https://gitlab.setic.ufsc.br/moodle-provas/local-exam_remote
    https://gitlab.setic.ufsc.br/moodle-provas/blocks-exam_actions

O código e instruções para gravação do "CD de Provas" podem ser encontrados em:

    https://gitlab.setic.ufsc.br/moodle-provas/livecd-provas

Instalação
==========

Este plugin deve ser instalado no "Moodle de Provas".
Este plugin é do tipo local, logo deve ser instalado no diretório "local", na raiz do seu moodle.
O nome diretório deste plugin dentro do diretório "local" deve ser "exam_authorization" (sem as aspas).
Após colocar o código do plugin no diretório correto, visite o seu Moodle como administrador para finalizar a instalação.

Pós-instalação
==============

Há um script em cli/configure_moodle_provas.php que realiza diversas operações de configuração, dentre elas:

* define papeis adicionais: proctor e monitor
* remove diversas permissões dos papeis estudante e professor;
* oculta/desativa diversos módulos e blocos (forum, message, etc)
* altera diversos parâmetros globais de configuração

Para executar este script través da linha de comando, você vai precisar do "php-cli" (php command line interface).
Com o php-cli instalado, você pode acessar o diretório do plugin e executar o script com os seguintes comandos:

    cd moodle/local/exam_authorization
    php cli/configure_moodle_provas.php

Licença
=======

Este código-fonte é distribuído sob licença GNU General Plublic License
Uma cópia desta licença está no arquivo COPYING.txt
Ela também pode ser vista em <http://www.gnu.org/licenses/>.
