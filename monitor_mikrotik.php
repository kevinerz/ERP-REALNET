<?php
date_default_timezone_set('Asia/Jakarta');

$CFG = [
  'host'      => '103.68.214.159',
  'community' => 'monitor',
  'timeout'   => 2000000,
  'retries'   => 1,

  // sesuaikan interface untuk traffic (nama harus sama dengan ifName di IF-MIB)
  'traffic_interfaces' => ['vlan2951-HSP', 'ether13', 'ether8'],

  // PPPoE active: prefix interface dinamis (ubah kalau beda)
  'pppoe_prefix' => 'pppoe-in',

  'pppoe_limit' => 200,
];

function jsonOut($data, $code=200){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

if (!function_exists('snmp2_get') || !function_exists('snmp2_real_walk')) {
  jsonOut(['success'=>false,'message'=>'PHP ext snmp belum aktif (install/enable php-snmp).'], 500);
}

snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

function snmpClean($val) {
  if ($val === false || $val === null) return null;
  $val = trim((string)$val);
  $val = preg_replace('/^[A-Z\-]+:\s*/', '', $val);     // buang "STRING:" / "Timeticks:"
  $val = preg_replace('/^"(.*)"$/', '$1', $val);        // buang quote
  return trim($val);
}
function snmpGet($host,$community,$oid,$timeout,$retries){
  return snmpClean(@snmp2_get($host,$community,$oid,$timeout,$retries));
}
function snmpRealWalk($host,$community,$oid,$timeout,$retries){
  $raw = @snmp2_real_walk($host,$community,$oid,$timeout,$retries);
  if ($raw === false || !is_array($raw)) return [];
  $out = [];
  foreach ($raw as $k => $v) $out[$k] = snmpClean($v);
  return $out;
}
function timeticksToSeconds($raw){
  if ($raw === null) return null;
  if (preg_match('/\((\d+)\)/', $raw, $m)) return ((int)$m[1]) / 100;
  if (ctype_digit($raw)) return ((int)$raw) / 100;
  return null;
}
function secondsToHuman($s){
  if ($s === null) return '-';
  $s = (int)round($s);
  $d = intdiv($s,86400); $s%=86400;
  $h = intdiv($s,3600);  $s%=3600;
  $m = intdiv($s,60);    $s%=60;
  if ($d>0) return "{$d}d {$h}h {$m}m {$s}s";
  if ($h>0) return "{$h}h {$m}m {$s}s";
  if ($m>0) return "{$m}m {$s}s";
  return "{$s}s";
}
function fmtBps($bps){
  $bps = (float)$bps;
  $u = ['bps','Kbps','Mbps','Gbps'];
  $i=0; while($bps>=1000 && $i<count($u)-1){$bps/=1000;$i++;}
  return number_format($bps, $i==0?0:2, ',', '.').' '.$u[$i];
}
function cachePath($host){
  return sys_get_temp_dir().'/mt_snmp_cache_'.preg_replace('/[^a-zA-Z0-9_\-\.]/','_',$host).'.json';
}
function loadCache($host){
  $p=cachePath($host);
  if(!is_file($p)) return [];
  $j=json_decode(@file_get_contents($p),true);
  return is_array($j)?$j:[];
}
function saveCache($host,$data){
  @file_put_contents(cachePath($host), json_encode($data));
}

// OID standar
const OID_SYS_NAME   = '1.3.6.1.2.1.1.5.0';
const OID_SYS_UPTIME = '1.3.6.1.2.1.1.3.0';

// IF-MIB
const OID_IFNAME_BASE  = '1.3.6.1.2.1.31.1.1.1.1';   // ifName.<ifIndex>
const OID_IFHCIN_BASE  = '1.3.6.1.2.1.31.1.1.1.6';   // ifHCInOctets.<ifIndex>
const OID_IFHCOUT_BASE = '1.3.6.1.2.1.31.1.1.1.10';  // ifHCOutOctets.<ifIndex>

$host=$CFG['host']; $community=$CFG['community']; $timeout=$CFG['timeout']; $retries=$CFG['retries'];

if (isset($_GET['ajax'])) {
  $ajax = $_GET['ajax'];

  if ($ajax === 'summary') {
    $name = snmpGet($host,$community,OID_SYS_NAME,$timeout,$retries);
    $up   = snmpGet($host,$community,OID_SYS_UPTIME,$timeout,$retries);
    $up_s = timeticksToSeconds($up);

    if ($name === null) jsonOut(['success'=>false,'message'=>'SNMP tidak merespon (cek firewall/allowed addresses/community).'], 500);

    jsonOut(['success'=>true,'data'=>[
      'sysName'=>$name,
      'uptime_raw'=>$up,
      'uptime_seconds'=>$up_s,
      'uptime_human'=>secondsToHuman($up_s),
      'time'=>date('Y-m-d H:i:s'),
    ]]);
  }

  if ($ajax === 'traffic') {
    // build ifName => ifIndex map (pakai real_walk supaya ada index)
    $walk = snmpRealWalk($host,$community,OID_IFNAME_BASE,$timeout,$retries);
    if (!$walk) jsonOut(['success'=>false,'message'=>'Gagal walk IF-MIB ifName (cek SNMP view/permission).'], 500);

    $ifMap = []; // name => index
    foreach ($walk as $oid => $ifName) {
      if ($ifName === null) continue;
      if (preg_match('/\.([0-9]+)$/', $oid, $m)) $ifMap[$ifName] = (int)$m[1];
    }

    $now = microtime(true);
    $cache = loadCache($host);
    $out = [];

    foreach ($CFG['traffic_interfaces'] as $ifName) {
      $idx = $ifMap[$ifName] ?? null;
      if (!$idx) {
        $out[] = ['interface'=>$ifName,'error'=>'ifIndex tidak ditemukan. Cek nama interface (ifName).','rx_bps'=>0,'tx_bps'=>0];
        continue;
      }

      $in  = snmpGet($host,$community,OID_IFHCIN_BASE.'.'.$idx,$timeout,$retries);
      $outv= snmpGet($host,$community,OID_IFHCOUT_BASE.'.'.$idx,$timeout,$retries);

      $in  = $in===null?null:(float)preg_replace('/[^0-9]/','',(string)$in);
      $outv= $outv===null?null:(float)preg_replace('/[^0-9]/','',(string)$outv);

      $key="if_$idx";
      $prev=$cache[$key] ?? null;

      $rx=0; $tx=0;
      if ($prev && $in!==null && $outv!==null) {
        $dt = $now - (float)$prev['t'];
        if ($dt > 0.5) {
          $din  = $in  - (float)$prev['in'];
          $dout = $outv- (float)$prev['out'];
          if ($din < 0)  $din  = 0;
          if ($dout < 0) $dout = 0;
          $rx = ($din*8)/$dt;
          $tx = ($dout*8)/$dt;
        }
      }

      $cache[$key]=['t'=>$now,'in'=>$in??0,'out'=>$outv??0];

      $out[]=[
        'interface'=>$ifName,
        'ifIndex'=>$idx,
        'rx_bps'=>(int)round($rx),
        'tx_bps'=>(int)round($tx),
        'rx_human'=>fmtBps($rx),
        'tx_human'=>fmtBps($tx),
      ];
    }

    saveCache($host,$cache);
    jsonOut(['success'=>true,'time'=>date('Y-m-d H:i:s'),'data'=>$out]);
  }

  if ($ajax === 'pppoe') {
    $prefix = $CFG['pppoe_prefix'];
    $walk = snmpRealWalk($host,$community,OID_IFNAME_BASE,$timeout,$retries);
    if (!$walk) jsonOut(['success'=>false,'message'=>'Gagal walk IF-MIB ifName.'], 500);

    $rows=[];
    foreach ($walk as $oid => $ifName) {
      if ($ifName === null) continue;
      if (stripos($ifName, $prefix) === 0) {
        $idx = null;
        if (preg_match('/\.([0-9]+)$/', $oid, $m)) $idx = (int)$m[1];
        $rows[]=['ifName'=>$ifName,'ifIndex'=>$idx];
      }
    }
    usort($rows, fn($a,$b)=>strcmp($a['ifName'],$b['ifName']));
    $rows=array_slice($rows,0,(int)$CFG['pppoe_limit']);

    jsonOut(['success'=>true,'prefix'=>$prefix,'count'=>count($rows),'rows'=>$rows]);
  }

  jsonOut(['success'=>false,'message'=>'Unknown ajax'], 400);
}

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Monitoring MikroTik (SNMP)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#0b1220;color:#e5e7eb}
    .card{background:#0f1b33;border:1px solid rgba(255,255,255,.08)}
    .muted{color:#9ca3af}
    .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
    .table{--bs-table-bg:transparent}
    .table td,.table th{color:#e5e7eb;border-color:rgba(255,255,255,.08)}
  </style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Monitoring MikroTik (SNMP)</h3>
      <div class="muted small">Host: <span class="mono"><?=htmlspecialchars($CFG['host'])?></span> · Community: <span class="mono"><?=htmlspecialchars($CFG['community'])?></span></div>
    </div>
    <button class="btn btn-sm btn-outline-light" onclick="reloadAll()">Refresh</button>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-4">
      <div class="card rounded-4 p-3">
        <div class="fw-semibold mb-2">Summary</div>
        <div class="small">
          <div class="muted">sysName</div>
          <div class="mono" id="s_name">-</div>
          <div class="muted mt-2">uptime</div>
          <div class="mono" id="s_uptime">-</div>
          <div class="muted mt-2">last update</div>
          <div class="mono" id="s_time">-</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card rounded-4 p-3">
        <div class="fw-semibold mb-2">Traffic</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Interface</th><th class="text-end">RX</th><th class="text-end">TX</th></tr></thead>
            <tbody id="t_body"><tr><td colspan="3" class="muted">-</td></tr></tbody>
          </table>
        </div>
        <div class="muted small mt-2">Endpoint: <span class="mono">?ajax=traffic</span></div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card rounded-4 p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold">PPPoE Active</div>
          <div class="muted small">Count: <span class="mono" id="p_count">0</span></div>
        </div>
        <div class="table-responsive" style="max-height:360px;overflow:auto">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>ifName</th><th class="text-end">ifIndex</th></tr></thead>
            <tbody id="p_body"><tr><td colspan="2" class="muted">-</td></tr></tbody>
          </table>
        </div>
        <div class="muted small mt-2">Prefix: <span class="mono"><?=htmlspecialchars($CFG['pppoe_prefix'])?></span></div>
      </div>
    </div>
  </div>
</div>

<script>
async function getJSON(u){ const r=await fetch(u,{cache:'no-store'}); return await r.json(); }

async function loadSummary(){
  const j=await getJSON('?ajax=summary');
  if(!j.success) return;
  document.getElementById('s_name').textContent = j.data.sysName || '-';
  document.getElementById('s_uptime').textContent = j.data.uptime_human || '-';
  document.getElementById('s_time').textContent = j.data.time || '-';
}

async function loadTraffic(){
  const j=await getJSON('?ajax=traffic');
  if(!j.success) return;
  const b=document.getElementById('t_body');
  b.innerHTML='';
  (j.data||[]).forEach(r=>{
    const tr=document.createElement('tr');
    const err=r.error?`<div class="muted small">(${r.error})</div>`:'';
    tr.innerHTML=`
      <td class="mono">${r.interface}${err}</td>
      <td class="text-end mono">${r.rx_human||'-'}</td>
      <td class="text-end mono">${r.tx_human||'-'}</td>`;
    b.appendChild(tr);
  });
  if((j.data||[]).length===0) b.innerHTML=`<tr><td colspan="3" class="muted">Tidak ada data.</td></tr>`;
}

async function loadPPPoE(){
  const j=await getJSON('?ajax=pppoe');
  if(!j.success) return;
  document.getElementById('p_count').textContent = j.count || 0;
  const b=document.getElementById('p_body');
  b.innerHTML='';
  (j.rows||[]).forEach(r=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`<td class="mono">${r.ifName||'-'}</td><td class="text-end mono">${r.ifIndex||'-'}</td>`;
    b.appendChild(tr);
  });
  if((j.rows||[]).length===0) b.innerHTML=`<tr><td colspan="2" class="muted">Tidak ada PPPoE aktif / prefix tidak cocok.</td></tr>`;
}

async function reloadAll(){ try{ await Promise.all([loadSummary(),loadTraffic(),loadPPPoE()]); }catch(e){} }

reloadAll();
setInterval(loadTraffic, 3000);
setInterval(loadSummary, 8000);
setInterval(loadPPPoE, 8000);
</script>
</body>
</html>
