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

/**
 * Language File for Meet
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die();

// Basic
$string['pluginname'] = 'Meet';
$string['pluginadministration'] = 'Administração de Meet';
$string['modulename'] = 'Meet';
$string['modulenameplural'] = 'Meet';
$string['modulename_link'] = 'mod/meet/view';
$string['modulename_help'] = 'Com o Google Meet, é fácil iniciar uma videochamada segura. Basta usar qualquer navegador da Web moderno ou fazer o download do app.';

// Settings
$string['settings_heading_google_api'] = 'API do Google Agenda';
$string['settings_heading_google_api_description'] = 'Esta seção configura a API do Google e as credenciais da conta de serviço do Google';
$string['settings_heading_google_recordings'] = 'Gravações';
$string['settings_heading_google_recordings_description'] = 'Esta seção configura a busca de gravações';
$string['settings_credentials'] = 'Arquivo de credenciais (.json)';
$string['settings_credentials_description'] = 'O arquivo JSON com os dados das credenciais da Conta de Serviço do Google';
$string['settings_calendar_owner'] = 'E-mail do proprietário do calendário';
$string['settings_calendar_owner_description'] = 'O e-mail do proprietário do Google Agenda que será usado para criar os eventos';
$string['settings_calendar_id'] = 'ID da Agenda';
$string['settings_calendar_id_description'] = 'O ID do Google Agenda que será usado para criar os eventos. Precisa ser criado com o email do proprietário';
$string['settings_recordings_fetch'] = 'Tempo de busca das gravações';
$string['settings_recordings_fetch_description'] = 'As gravações são buscadas sempre que uma instância é exibida. Essa configuração define quanto tempo após o término da reunião eles ainda serão buscados. O padrão é 7 dias';
$string['settings_recordings_cache'] = 'Tempo de cache das gravações';
$string['settings_recordings_cache_description'] = 'As gravações são buscadas sempre que uma instância é exibida. Essa configuração define o tempo de cache para essa busca. O padrão é 2 horas';

// Form general
$string['form_block_general'] = 'Geral';
$string['form_field_name'] = 'Nome';
$string['form_field_intro'] = 'Descrição';
$string['form_field_timestart'] = 'Começa em';
$string['form_field_timestart_help'] = 'Data e hora de início da conferência';
$string['form_field_timeend'] = 'Termina em';
$string['form_field_timeend_help'] = 'Data e hora de término da conferência';
$string['form_field_notify'] = 'Notificar participantes';
$string['form_field_notify_help'] = 'Se marcado, todos os participantes serão notificados das alterações deste evento por e-mail';
$string['form_field_description'] = 'Descrição';
$string['form_field_visible'] = 'Visível';

// Form reminders
$string['form_block_reminders'] = 'Lembretes';
$string['form_label_reminder_count'] = 'Lembrete {no}';
$string['form_label_reminder_count_help'] = 'Definir um lembrete para os participantes';
$string['form_field_reminder_option_email'] = 'E-mail';
$string['form_field_reminder_option_popup'] = 'Notificação';
$string['form_field_reminder_option_minutes'] = 'Minutos';
$string['form_field_reminder_option_hours'] = 'Horas';
$string['form_field_reminder_option_days'] = 'Dias';
$string['form_button_add_reminder'] = 'Adicionar lembrete';

// Course module
$string['join'] = 'Participar';
$string['recordings'] = 'Gravações';
$string['play'] = 'Reproduzir';
$string['name'] = 'Nome';
$string['description'] = 'Descrição';
$string['thumbnail'] = 'Thumbnail';
$string['date'] = 'Data';
$string['duration'] = 'Duração';
$string['actions'] = 'Ações';
$string['delete_recording'] = 'Excluir a gravação "{$a}"';
$string['edit_recording'] = 'Editar a gravação "{$a}"';
$string['editing_recording'] = 'Editando gravação';
$string['hide_recording'] = 'Ocultar a gravação "{$a}"';
$string['show_recording'] = 'Mostrar a gravação "{$a}"';
$string['recording_deleted'] = 'A gravação "{$a->title}" foi excluída';
$string['no_recordings'] = 'Não há gravações para serem exibidas.';
$string['error_recording'] = 'A gravação não foi encontrada.';
$string['update_recordings'] = 'Atualizar gravações';
$string['update_recordings_help'] = 'As gravações são atualizadas automaticamente. Contudo, se algo deu errado e elas estiverem demorando para serem atualizadas, você pode fazer isto manualmente.';
$string['meeting_room_not_available'] = 'Esta sala de reuniões ainda não está disponível.';
$string['meeting_room_available'] = 'A sala de reuniões está pronta.';
$string['meeting_room_closed'] = 'A sala de reuniões está fechada.';
$string['meeting_room_see_recordings'] = 'Você pode visualizar as gravações (nem sempre disponíveis) abaixo.';
$string['meeting_room_forbidden'] = 'YVocê não pode ingressar nesta sala de reuniões.';

// Capabilities
$string['meet:addinstance'] = 'Adicionar uma nova atividade do Meet';
$string['meet:view'] = 'Visualizar uma atividade do Meet';
$string['meet:join'] = 'Participar de uma reunião em uma atividade do Meet';
$string['meet:playrecordings'] = 'Reproduzir uma gravação de uma reunião do Meet';
$string['meet:managerecordings'] = 'Gerenciar gravações do Meet';

// Events
$string['event_meeting_joined'] = 'Juntou-se à reunião';
$string['event_recording_played'] = 'Gravação reproduzida';
$string['event_recording_updated'] = 'Gravação atualizada';
$string['event_recording_deleted'] = 'Gravação excluída';
$string['event_recording_manually_fetched'] = 'Gravação buscada manualmente';
$string['event_recording_automatically_fetched'] = 'Gravação buscada automaticamente';

// Errors
$string['invalid_access'] = 'Acesso inválido.';
