<?php // $Id$

$string['pluginname'] = 'Controle de Acesso ao Moodle Provas';

$string['disable_header_check'] = 'Desativar verificação de uso do CD';
$string['disable_header_check_desc'] = 'Se marcada, esta opção desativa completamenta a verificação de acesso ao Moodle provas via CD de Provas.';
$string['header_version'] = 'Versão mínima do CD';
$string['header_version_descr'] = 'Versão mínima (ex: 1.3) suportada do CD Moodle Provas. Deixe em branco para desabilitar esta verificação.';
$string['client_host_timeout'] = 'Tempo de expiração do cliente';
$string['client_host_timeout_descr'] = 'Tempo máximo (em minutos) entre a última notificação de vida do cliente e a autenticação do estudante.
    Utilize 0 (zero) para desabilitar esta verificação.';
$string['ip_ranges_editors'] = 'Faixas IP para elaboradores de provas';
$string['ip_ranges_editors_descr'] = 'Faixas de restrição de endereços IP (lista separada por ;)
    a partir dos quais pessoas podem acessar o Moodle para elaborar provas. Deixe em branco para desabilitar esta verificação.
    Ex: 10.0.\*.\*; 192.168.0.\*';
$string['ip_ranges_students'] = 'Faixas IP para estudantes';
$string['ip_ranges_students_descr'] = 'Faixas de restrição de endereços IP (lista separada por ;)
    a partir dos quais estudantes podem acessar o Moodle para realizar provas. Deixe em branco para desabilitar esta verificação.
    Ex: 10.0.\*.\*; 192.168.0.\*';

$string['remote_moodles'] = 'Instalações remotas de Moodle integradas ao Moodle Provas';
$string['remote_moodle'] = 'Instalação remota de Moodle integrada ao Moodle Provas';

$string['identifier'] = 'Identificador';
$string['identifier_help'] = 'Palavra única que identifica o Moodle remoto';
$string['description'] = 'Descrição do Moodle';
$string['description_help'] = 'Frase que descreve o Moodle Remoto. Esta frase é utilizado como nome de categoria onde serão
    postos os cursos Moodle relacionados ao Moodle Remoto.';
$string['url'] = 'URL do Moodle';
$string['url_help'] = 'URL da instalação remota do Moodle';
$string['token'] = 'Token';
$string['token_help'] = 'Token do serviço web do Moodle remoto que possibilida troca de dados entre as duas implantações de Moodle';

$string['not_configured'] = 'Módulo de Controle de Acesso ao Moodle Provas não está corretamente configurado.';
$string['invalid_context'] = 'Deve ser uma palavra única';

$string['confirmdelete'] = 'Realmente remover a relação com a instalação remota de Moodle: \'{$a}\'?';
$string['already_exists'] = 'Já há outro registro com este valor.';
$string['access_key_timedout'] = 'Chave de acesso com validade expirada';
$string['access_key_unknown'] = 'Chave de acesso desconhecida';
$string['unknown_identifier'] = 'Identificador de Moodle desconhecido: \'{$a}\'';
$string['return_null'] = 'Há algum problema com a configuração do Moodle remoto \'{$a}\' pois retornou valor nulo ao ser chamado';
$string['no_access_permission'] = 'O acesso a este ambiente é restrito a:
    <UL>
    <LI>elaboradores de prova;</LI>
    <LI>pessoas responsáveis pela aplicação ou monitoramente de provas;</LI>
    <LI>estudantes durante a realização de uma prova, após o computador ter sido liberado pelo responsável para realização de prova.</LI>
    </UL>
    Suas credenciais não o habilitam a realizar nenhuma destas operações neste momento, razão pela qual seu acesso foi negado.';
$string['no_student_permission'] = 'Este computador está liberado para realização de provas, razão pela qual o acesso é permitido apenas a estudantes
    durante a realização da prova correspondente à chave de acesso utilizada para liberá-lo.';
$string['more_than_student_permission'] = 'Este computador está liberado para realização de provas, porém você tem outros papeis além de estudante
    curso Moodle correspondente à chave de acesso utilizada para liberá-lo.';
$string['course_not_avaliable'] = 'Curso Moodle correspondente à chave de acesso informada inexistente ou indisponível.';
$string['out_of_ip_ranges'] = 'Operação não permitida a partir deste computador em função de restrição de números IP';

$string['proctor_roleid'] = 'Papel para responsáveis';
$string['proctor_roleid_descr'] = 'Papel com os quais os responsáveis por aplicar provas são inscritos nos cursos Moodle.';
$string['monitor_roleid'] = 'Papel para monitores';
$string['monitor_roleid_descr'] = 'Papel com os quais pessoas que monitoram provas são inscritos nos cursos Moodle.';
