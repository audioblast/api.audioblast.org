<?php

function phymoji_info() {
  $info = array(
    "mname" => "phymoji",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Phylogenetic Emoji",
    "desc" => "Finds an emoji for a scientific name.",
    "endpoints" => array(
      "get_phymoji" => array(
        "callback" => "phymoji_phymoji",
        "desc" => "Finds an emoji for a scientific name.",
        "returns" => "data",
        "params" => array(
          "names" => array(
            "desc" => "Array of names from lowest to highest rank",
            "type" => "string",
            "op" => "=",
            "default" => "",
          ),
          "output" => array(
            "desc" => "At present just an array",
            "type" => "string",
            "allowed" => array(
              "JSON"
            ),
            "default" => "JSON"
          )
        )
      )
    )
  );
  return($info);
}

function phymoji_phymoji($f) {
  //TODO: Include below as sep file
  $ret = array();
  $ret["data"] = phymoji($f['names']);
  return($ret);
}

function getPhymoji() {
    $phymoji = array(
    "primates" => "🐒",
    "haplorhini" => "🐒",
    "gorilla" => "🦍",
    "hominidae" => "🦧",
    "canidae" => "🐺",
    "carnivora" => "🐺",
    "canis familiaris" => "🐕",
    "vulpes" => "🦊",
    "procyonidae" => "🦝",
    "felis" => "🐈",
    "felidae" => "🐆",
    "panthera" => "🦁",
    "panthera leo" => "🦁",
    "panthera tigris" => "🐅",
    "panthera pardus" => "🐆",
    "equus ferus" => "🐎",
    "equus grevyi" => "🦓",
    "equus quagga" => "🦓",
    "equus zebra" => "🦓",
    "equidae" => "🐎",
    "hippotigris" => "🦓",
    "perissodactyla" => "🐎",
    "cervidae" => "🦌",
    "artiodactyla" => "🦌",
    "bison" => "🦬",
    "bos taurus" => "🐄",
    "bovidae" => "🐄",
    "bubalus" => "🐃",
    "sus domesticus" => "🐖",
    "suidae" => "🐗",
    "caprinae" => "🐏",
    "ovis" => "🐏",
    "capra" => "🐐",
    "camelus bactrianus" => "🐫",
    "camelidae" => "🐪",
    "lama" => "🦙",
    "giraffidae" => "🦒",
    "proboscidea" => "🐘",
    "mammuthus" => "🦣",
    "rhinocerotidae" => "🦏",
    "hippopotamidae" => "🦛",
    "rodentia" => "🐁",
    "mus" => "🐁",
    "rattus" => "🐀",
    "cricetidae" => "🐹",
    "lagomorpha" => "🐇",
    "sciuridae" => "🐿️",
    "castoridae" => "🦫",
    "eulipotyphla" => "🦔",
    "chiroptera" => "🦇",
    "ursidae" => "🐻",
    "ursus maritimus" => "🐻‍❄️",
    "phascolarctos" => "🐨",
    "marsupialia" => "🦘",
    "ailuropoda" => "🐼",
    "pilosa" => "🦥",
    "lutrinae" => "🦦",
    "mustelidae" => "🦨",
    "mephitidae" => "🦨",
    "meles" => "🦡",
    "galliformes" => "🦃",
    "gallus" => "🐓",
    "aves" => "🐦",
    "sphenisciformes" => "🐧",
    "columbiformes" => "🕊️",
    "accipitriformes" => "🦅",
    "anseriformes" => "🦆",
    "cygnus" => "🦢",
    "strigiformes" => "🦉",
    "raphinae" => "🦤",
    "phoenicopteridae" => "🦩",
    "pavoninae" => "🦚",
    "psittaciformes" => "🦜",
    "amphibia" => "🐸",
    "crocodilia" => "🐊",
    "testudines" => "🐢",
    "reptilia" => "🦎",
    "serpentes" => "🐍",
    "sauropoda" => "🦕",
    "dinosauria" => "🦖",
    "cetacea" => "🐋",
    "delphinidae" => "🐬",
    "pinnipedia" => "🦭",
    "osteichthyes" => "🐟",
    "pterophyllum" => "🐠",
    "tetraodontidae" => "🐡",
    "chondrichthyes" => "🦈",
    "cephalopoda" => "🐙",
    "anthozoa" => "🪸",
    "mollusca" => "🐌",
    "lepidoptera" => "🦋",
    "arthropoda" => "🐛",
    "formicidae" => "🐜",
    "hymenoptera" => "🐝",
    "coleoptera" => "🪲",
    "coccinellidae" => "🐞",
    "orthoptera" => "🦗",
    "blattodea" => "🪳",
    "arachnida" => "🕷️",
    "scorpiones" => "🦂",
    "diptera" => "🪰",
    "annelida" => "🪱",
    "culicoidea" => "🦟",
    "bacteria" => "🦠",
    "prunus" => "🌸",
    "nelumbonaceae" => "🪷",
    "rosales" => "🌹",
    "plantae" => "🌱",
    "coleoidea" => "🦑",
    "crustacea" => "🦐",
    "nephropidae" => "🦞",
    "brachyura" => "🦀",
    "fagaceae" => "🌰",
    "fungi" => "🍄",
    "acer" => "🍁",
    "fabaceae" => "🍀",
    "trifolium" => "☘️",
    "poales" => "🌾",
    "cactaceae" => "🌵",
    "arecaceae" => "🌴",
    "pinales" => "🌲",
    "liliales" => "🌷",
    "asterales" => "🌻",
    "malvales" => "🌺",
    "vitaceae" => "🍇",
    "cucurbitaceae" => "🍈",
    "rutaceae" => "🍊",
    "faboideae" => "🥜",
    "allioideae" => "🧅",
    "allium sativum" => "🧄",
    "brassicales" => "🥦",
    "cucumis" => "🥒",
    "capsicum" => "🌶️",
    "zea" => "🌽",
    "apiaceae" => "🥕",
    "solanum tuberosum" => "🥔",
    "solanum melongena" => "🍆",
    "laurales" => "🥑",
    "cocos" => "🥥",
    "lamiales" => "🫒",
    "solanales" => "🍅",
    "actinidiaceae" =>"🥝",
    "ericales" => "🫐",
    "fragaria" => "🍓",
    "prunus persicus" =>"🍑",
    "pyrus" => "🍐",
    "maleae" => "🍎",
    "sapindales" => "🥭",
    "bromeliaceae" => "🍍",
    "zingiberales" => "🍌"
    );
    return($phymoji);
}

function phymoji($names) {
  $data = getPhymoji();
  foreach ($names as $name) {
    if (array_key_exists($name, $data)) {
      $ret = array(
        "emoji" => $data[$name],
        "match" => $name
      );
      return($ret);
    }
  }
  return(array("emoji" => "", "match"=>""));
}
