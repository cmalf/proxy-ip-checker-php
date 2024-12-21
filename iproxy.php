<?php
$CoderMarkPrinted = false;

$cl = [
    'gr' => "\033[32m",
    'br' => "\033[34m",
    'red' => "\033[31m",
    'yl' => "\033[33m",
    'gb' => "\033[4m",
    'or' => "\033[35m",
    'cy' => "\033[36m",
    'oc' => "\033[95m",
    'am' => "\033[91m",
    'rt' => "\033[0m"
];

function CoderMark($cl, &$CoderMarkPrinted) {
    if (!$CoderMarkPrinted) {
        echo "
╭━━━╮╱╱╱╱╱╱╱╱╱╱╱╱╱╭━━━┳╮
┃╭━━╯╱╱╱╱╱╱╱╱╱╱╱╱╱┃╭━━┫┃{$cl['gr']}
┃╰━━┳╮╭┳━┳━━┳━━┳━╮┃╰━━┫┃╭╮╱╭┳━╮╭━╮
┃╭━━┫┃┃┃╭┫╭╮┃╭╮┃╭╮┫╭━━┫┃┃┃╱┃┃╭╮┫╭╮╮{$cl['br']}
┃┃╱╱┃╰╯┃┃┃╰╯┃╰╯┃┃┃┃┃╱╱┃╰┫╰━╯┃┃┃┃┃┃┃
╰╯╱╱╰━━┻╯╰━╮┣━━┻╯╰┻╯╱╱╰━┻━╮╭┻╯╰┻╯╰╯{$cl['rt']}
╱╱╱╱╱╱╱╱╱╱╱┃┃╱╱╱╱╱╱╱╱╱╱╱╭━╯┃
╱╱╱╱╱╱╱╱╱╱╱╰╯╱╱╱╱╱╱╱╱╱╱╱╰━━╯
\n{$cl['gr']} Proxy IP Checker {$cl['am']} v.0.1 {$cl['rt']}
{$cl['gr']}--------------------------------------
\n{$cl['yl']}[+]{$cl['rt']} GH : {$cl['br']}https://github.com/cmalf/
\n{$cl['gr']}--------------------------------------
        ";
        $CoderMarkPrinted = true;
    }
}

function checkProxy($proxyLine) {
    try {
        $parts = explode('://', $proxyLine);
        if (count($parts) !== 2) {
            throw new Exception('Invalid proxy format');
        }
        
        [$protocol, $rest] = $parts;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://ipinfo.io/json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROXY, $rest);
        curl_setopt($ch, CURLOPT_PROXYTYPE, $protocol === 'http' ? CURLPROXY_HTTP : CURLPROXY_SOCKS5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36');

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception($error);
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: $httpCode");
        }

        $data = json_decode($response, true);
        if (!$data) {
            throw new Exception('Invalid response from server');
        }

        return [
            'proxy' => $proxyLine,
            'success' => true,
            'ip' => $data['ip'],
            'country' => $data['country'],
            'region' => $data['region'],
            'city' => $data['city']
        ];
    } catch (Exception $error) {
        return [
            'proxy' => $proxyLine,
            'success' => false,
            'error' => $error->getMessage()
        ];
    }
}

function checkProxyList() {
    global $cl;
    try {
        $proxyList = array_filter(
            array_map(
                'trim',
                file('proxy.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            )
        );

        echo "Loading " . count($proxyList) . " proxies...\n";

        $results = [];
        foreach ($proxyList as $proxy) {
            $result = checkProxy($proxy);
            
            if ($result['success']) {
                echo "{$cl['br']}{$proxy}{$cl['rt']} -> IP: {$cl['gr']}{$result['ip']}{$cl['rt']} ({$result['country']})\n";
            } else {
                echo "{$cl['red']}{$proxy}{$cl['rt']} -> IP: {$cl['oc']}{$result['error']}{$cl['rt']}\n";
            }
            
            $results[] = $result;
        }

        $timestamp = date('Y-m-d-H-i-s');
        $outputFile = "proxy_check_results_{$timestamp}.json";
        
        file_put_contents($outputFile, json_encode($results, JSON_PRETTY_PRINT));
        echo "\nResults saved to {$outputFile}\n";

        $working = count(array_filter($results, fn($r) => $r['success']));
        echo "\nSummary:\n";
        echo "Total proxies: " . count($results) . "\n";
        echo "Working: {$working}\n";
        echo "Failed: " . (count($results) - $working) . "\n";

    } catch (Exception $error) {
        echo 'Error reading proxy file: ' . $error->getMessage() . "\n";
    }
}

// Run the checker
CoderMark($cl, $CoderMarkPrinted);
checkProxyList();
