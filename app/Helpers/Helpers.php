<?php
function responseFormat($data, int $code = 200)
{
    $status = $code >= 200 && $code < 300;
    return response()->json(
        [
            'status' => $status,
            'data' => $data,
        ],
        $code
    );
}
function extractPhone($remoteJid, $remoteJidAlt)
{
    $jidList = [$remoteJid, $remoteJidAlt];
    foreach ($jidList as $jid) {
        if (!$jid) continue;
        if (preg_match('/^(\d+)@s\.whatsapp\.net$/', $jid, $matches)) {
            return $matches[1];
        }
    }
    return null;
}
