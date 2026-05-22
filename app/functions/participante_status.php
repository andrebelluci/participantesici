<?php

const PARTICIPANTE_STATUS_ATIVO = 'ativo';
const PARTICIPANTE_STATUS_INATIVO = 'inativo';
const PARTICIPANTE_STATUS_NAO_PODE_PARTICIPAR = 'nao_pode_participar';

const PARTICIPANTE_STATUS_VALIDOS = [
  PARTICIPANTE_STATUS_ATIVO,
  PARTICIPANTE_STATUS_INATIVO,
  PARTICIPANTE_STATUS_NAO_PODE_PARTICIPAR,
];

/**
 * @return array<string, string>
 */
function participanteStatusLabels(): array
{
  return [
    PARTICIPANTE_STATUS_ATIVO => 'Ativo',
    PARTICIPANTE_STATUS_INATIVO => 'Inativo',
    PARTICIPANTE_STATUS_NAO_PODE_PARTICIPAR => 'Não pode participar',
  ];
}

function participanteStatusLabel(string $status): string
{
  $labels = participanteStatusLabels();
  return $labels[$status] ?? 'Ativo';
}

function participantePodeVincularRituais(string $status): bool
{
  return $status === PARTICIPANTE_STATUS_ATIVO;
}

/** Garante um status válido (fallback: ativo). */
function participanteNormalizarStatus(?string $status): string
{
  if ($status !== null && $status !== '' && in_array($status, PARTICIPANTE_STATUS_VALIDOS, true)) {
    return $status;
  }
  return PARTICIPANTE_STATUS_ATIVO;
}

/**
 * @return array{status: string, motivo_status: ?string, error: ?string}
 */
function participanteProcessarStatusPost(?string $statusRaw, ?string $motivoRaw): array
{
  $status = $statusRaw ?? PARTICIPANTE_STATUS_ATIVO;
  if (!in_array($status, PARTICIPANTE_STATUS_VALIDOS, true)) {
    return [
      'status' => PARTICIPANTE_STATUS_ATIVO,
      'motivo_status' => null,
      'error' => 'Status do participante inválido.',
    ];
  }

  $motivo = $motivoRaw !== null ? trim($motivoRaw) : null;
  if ($motivo === '') {
    $motivo = null;
  }

  if ($status === PARTICIPANTE_STATUS_ATIVO) {
    return ['status' => $status, 'motivo_status' => null, 'error' => null];
  }

  if ($status === PARTICIPANTE_STATUS_NAO_PODE_PARTICIPAR && $motivo === null) {
    return [
      'status' => $status,
      'motivo_status' => null,
      'error' => 'Informe o motivo para o status "Não pode participar".',
    ];
  }

  return ['status' => $status, 'motivo_status' => $motivo, 'error' => null];
}

/**
 * Filtros de status para listagem (padrão: somente ativos).
 *
 * @return array{where: string, params: array, selecionados: array}
 */
function participanteFiltroStatusFromRequest(): array
{
  $selecionados = [];
  if (isset($_GET['filtro_status']) && is_array($_GET['filtro_status'])) {
    foreach ($_GET['filtro_status'] as $s) {
      if (in_array($s, PARTICIPANTE_STATUS_VALIDOS, true)) {
        $selecionados[] = $s;
      }
    }
    $selecionados = array_values(array_unique($selecionados));
  }

  if (empty($selecionados)) {
    $selecionados = [PARTICIPANTE_STATUS_ATIVO];
  }

  $placeholders = implode(',', array_fill(0, count($selecionados), '?'));
  return [
    'where' => " AND p.status IN ($placeholders)",
    'params' => $selecionados,
    'selecionados' => $selecionados,
  ];
}

function participanteStatusBadgeClass(string $status): string
{
  return match ($status) {
    PARTICIPANTE_STATUS_INATIVO => 'bg-orange-100 text-orange-700 hover:bg-orange-200 border border-orange-300',
    PARTICIPANTE_STATUS_NAO_PODE_PARTICIPAR => 'bg-red-100 text-red-700 hover:bg-red-200 border border-red-500',
    default => 'bg-green-100 text-green-700',
  };
}

/** Banner no topo da visualização do participante (classes presentes no tailwind.css compilado). */
function participanteStatusBannerClasses(string $status): string
{
  return match ($status) {
    PARTICIPANTE_STATUS_NAO_PODE_PARTICIPAR => 'bg-red-100 border-l-4 border-red-500 text-red-800',
    PARTICIPANTE_STATUS_INATIVO => 'bg-orange-100 border-l-4 border-orange-300 text-orange-700',
    default => 'bg-gray-100 border-l-4 border-gray-300 text-gray-800',
  };
}

function participanteStatusBannerIconClass(string $status): string
{
  return match ($status) {
    PARTICIPANTE_STATUS_NAO_PODE_PARTICIPAR => 'fa-ban text-red-700',
    PARTICIPANTE_STATUS_INATIVO => 'fa-user-slash text-orange-700',
    default => 'fa-circle-info text-gray-600',
  };
}

/** Botão "Ver motivo" no padrão Documentos (laranja / vermelho). */
function participanteStatusMotivoBtnClasses(string $status): string
{
  $base = 'js-abrir-motivo-status px-4 py-2 rounded-lg flex items-center gap-2 transition font-semibold text-sm shrink-0';
  return match ($status) {
    PARTICIPANTE_STATUS_NAO_PODE_PARTICIPAR => $base . ' bg-red-100 hover:bg-red-200 text-red-700 border border-red-500',
    PARTICIPANTE_STATUS_INATIVO => $base . ' bg-orange-100 hover:bg-orange-200 text-orange-700 border border-orange-300',
    default => $base . ' bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300',
  };
}

/**
 * Texto exibido na modal de motivo (mesmo em todos os botões da mesma tela).
 */
function participanteMotivoParaModal(?string $motivo, string $status): string
{
  $motivo = $motivo !== null ? trim($motivo) : '';
  if ($motivo !== '') {
    return $motivo;
  }

  return match ($status) {
    PARTICIPANTE_STATUS_INATIVO => 'Nenhuma observação registrada.',
    PARTICIPANTE_STATUS_NAO_PODE_PARTICIPAR => 'Motivo não informado',
    default => 'Motivo não informado',
  };
}

/** Escapa texto para data-status-motivo / data-status-titulo (evita quebrar HTML/JS). */
function participanteEscaparDataAttr(string $texto): string
{
  $texto = str_replace(["\r\n", "\r"], "\n", $texto);
  $texto = str_replace("\n", ' ', $texto);
  return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
