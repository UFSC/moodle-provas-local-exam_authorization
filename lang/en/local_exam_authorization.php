<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
//
// Este bloco é parte do Moodle Provas - http://tutoriais.moodle.ufsc.br/provas/
// Este projeto é financiado pela
// UAB - Universidade Aberta do Brasil (http://www.uab.capes.gov.br/)
// e é distribuído sob os termos da "GNU General Public License",
// como publicada pela "Free Software Foundation".

/**
 * Language strings for local-exam_authorization plugin.
 *
 * @package    local_exam_authorization
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
$string['out_of_ip_ranges'] = 'Operação não permitida a partir deste computador em função de restrição de números IP.';
$string['out_of_editor_ip_range'] = 'Operação de disponibilização e edição de cursos (incluindo elaboração de provas) não permitida a partir
    deste computador em função de restrição de números IP definida pelo administrador na configuração do Moodle Provas. Em caso de dúvidas,
    por favor contate o administrador do Moodle Provas.';

$string['proctor_roleid'] = 'Papel para responsáveis';
$string['proctor_roleid_descr'] = 'Papel com os quais os responsáveis por aplicar provas são inscritos nos cursos Moodle.';
$string['monitor_roleid'] = 'Papel para monitores';
$string['monitor_roleid_descr'] = 'Papel com os quais pessoas que monitoram provas são inscritos nos cursos Moodle.';

$string['update_password'] = 'Atualizar senhas de estudantes';
$string['update_password_descr'] = 'Atualizar senhas de estudantes (apenas para auth/manual) sempre que forem feitas ou revisadas as inscrições nos cursos.';
$string['auth_plugin'] = 'Método de autenticação';
$string['auth_plugin_descr'] = 'Método de autenticação para novos usuários que sejam cadastrados no Moodle quando sejam feitas ou revisadas as inscrições.
    Caso seja selecionada a opção \'Método externo\', o método de autenticação preferencial é o mesmo utilizado pelo usuário no Moodle remoto.
    Se esse método não estiver disponível e ativo localmente, será utilizado o método \'manual\'.';
$string['default_auth_plugin'] = 'Método externo';

$string['browser_no_version_header'] = 'Não foi possível validar a versão do CD de Provas.';
$string['browser_invalid_version_header'] = 'Versão inválida do CD de Provas.';
$string['browser_old_version'] = 'Versão antiga do CD de Provas.';

$string['has_student_session'] = 'Por questões de segurança só pode haver uma sessão ativa de um mesmo usuário no Moodle Provas. Como foi detectada a existência de uma sessão que está sendo utilizada para realização de prova, seu acesso foi bloqueado até o encerramento da prova ou que expire o tempo de vida dessa sessão.';
$string['session_removed'] = 'Por questões de segurança só pode haver uma sessão ativa de um mesmo usuário no Moodle Provas. Desta forma, foi removida \'{$a}\' outra sessão que estava ativa em seu nome.';
$string['sessions_removed'] = 'Por questões de segurança só pode haver uma sessão ativa de um mesmo usuário no Moodle Provas. Desta forma, foram removidas \'{$a}\' sessões que estavam ativas em seu nome.';
