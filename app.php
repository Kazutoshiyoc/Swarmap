<?php

	set_time_limit(300);
	// ini_set('display_errors', 0);

	// Forsquare
	$consumer_key = '';
	$consumer_secret = '';
	$redirect_uri = 'https://example.com/app.php';

	// Google
	$google_map_api_key = '';

	// KML初期化
	$rootNode = new SimpleXMLElement( "<?xml version='1.0' encoding='UTF-8'?><kml xmlns='http://www.opengis.net/kml/2.2'></kml>" );
	$docNode = $rootNode->addChild( 'Document' );
	$docNode->addChild( 'name', 'Swarmap' );
	$styleNode = $docNode->addChild( 'Style' );
	$styleNode->addAttribute( 'id', 'swarmap' );
	$iconStyleNode = $styleNode->addChild( 'IconStyle' );
	$iconStyleNode->addChild( 'scale', '0.5' );
	$iconNode = $iconStyleNode->addChild( 'Icon' );
	$iconNode->addChild( 'href', 'https://example.com/img/plot.png' );
	$labelStyleNode = $styleNode->addChild( 'LabelStyle' );
	$labelStyleNode->addChild( 'scale', '0.1' );

	//「許可」された場合の処理
	if( isset( $_GET['code'] ) && !empty( $_GET['code'] ) && is_string( $_GET['code'] ) ) {
		// アクセストークンの取得に利用するコード
		$code = $_GET['code'];

		// リクエストURL
		$request_url = 'https://foursquare.com/oauth2/access_token?client_id=' . $consumer_key . '&client_secret=' . $consumer_secret . '&grant_type=authorization_code&redirect_uri=' . rawurlencode( $redirect_uri ) . '&code=' . $_GET['code'];

		// アクセストークンの取得
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $request_url );
		curl_setopt( $curl, CURLOPT_HEADER, 1 ); 
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 5 );
		$res1 = curl_exec( $curl );
		$res2 = curl_getinfo( $curl );
		curl_close( $curl );

		$json = substr( $res1, $res2['header_size'] );
		$obj = json_decode( $json );

		// アクセストークンを取得できなかった場合の処理
		if( !isset( $obj->access_token ) )	$error = 'アクセストークンを取得できませんでした。';
		else	$access_token = $obj->access_token;
	}

	// 「拒否」された場合の処理
	elseif( isset( $_GET['error'] ) )	$error = 'アクセスは拒否されました。';

	// ボタンを表示
	// 設定項目を表示
	// アクセストークンを次のページに送る

	// 初期値取得
		$params = array(
			'oauth_token' => $access_token,
			'locale' => 'ja',
			'm' => 'swarm',
			'v' => '20171010',
			'limit' => '1',
			'sort' => 'newestfirst',
		);
		// GETメソッドで指定がある場合
		foreach( array( 'locale', 'm', 'limit', 'sort', 'afterTimestamp', 'beforeTimestamp' ) as $val ) {
			if( isset( $_GET[ $val ] ) && $_GET[ $val ] != '' )	$params[ $val ] = $_GET[ $val ];
		}
		// リクエストURL
		$request_url = 'https://api.foursquare.com/v2/users/self/checkins' . '?' . http_build_query( $params );
		// cURLでリクエスト
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $request_url );
		curl_setopt( $curl, CURLOPT_HEADER, 1 ); 
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 5 );
		$res1 = curl_exec( $curl );
		$res2 = curl_getinfo( $curl );
		curl_close( $curl );

		$json = substr( $res1, $res2['header_size'] );
		$obj = json_decode( $json );
		$checkin_num = $obj->response->checkins->count;
		foreach( $obj->response->checkins->items as $item ) {
			$createdAt = $item->createdAt + $item->timeZoneOffset;
			$end_time = date( 'Y/m/d', $createdAt );
		}

	// User ID取得
		$params = array(
			'oauth_token' => $access_token,
			'locale' => 'ja',
			'm' => 'swarm',
			'v' => '20171010',
			'limit' => '1',
			'sort' => 'newestfirst',
		);
		// GETメソッドで指定がある場合
		foreach( array( 'locale', 'm' ) as $val ) {
			if( isset( $_GET[ $val ] ) && $_GET[ $val ] != '' )	$params[ $val ] = $_GET[ $val ];
		}
		// リクエストURL
		$request_url = 'https://api.foursquare.com/v2/users/self' . '?' . http_build_query( $params );
		// cURLでリクエスト
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $request_url );
		curl_setopt( $curl, CURLOPT_HEADER, 1 ); 
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 5 );
		$res1 = curl_exec( $curl );
		$res2 = curl_getinfo( $curl );
		curl_close( $curl );

		$json = substr( $res1, $res2['header_size'] );
		$obj = json_decode( $json );
		$user_id = $obj->response->user->id;
		$first_name = $obj->response->user->firstName;
		$last_name = $obj->response->user->lastName;

	$num = 0;
	$cnt = 0;
	$lim = 250;
	while ($cnt < $checkin_num) {
		// 設定項目
		$params = array(
			'oauth_token' => $access_token,
			'locale' => 'ja',
			'm' => 'swarm',	// モード (foursquare OR swarm)
			'v' => '20171010',	// バージョン
			'limit' => "$lim",	// 取得件数
			'sort' => 'newestfirst',
			'beforeTimestamp' => "$createdAt",
		);
		// GETメソッドで指定がある場合
		foreach( array( 'locale', 'm', 'limit', 'sort', 'afterTimestamp', 'beforeTimestamp' ) as $val ) {
			if( isset( $_GET[ $val ] ) && $_GET[ $val ] != '' )	$params[ $val ] = $_GET[ $val ];
		}

		// リクエストURL
		$request_url = 'https://api.foursquare.com/v2/users/self/checkins' . '?' . http_build_query( $params );

		// cURLでリクエスト
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $request_url );
		curl_setopt( $curl, CURLOPT_HEADER, 1 ); 
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 5 );
		$res1 = curl_exec( $curl );
		$res2 = curl_getinfo( $curl );
		curl_close( $curl );

		$json = substr( $res1, $res2['header_size'] );
		$obj = json_decode( $json );

		// エラー判定
		if( !$obj || !isset($obj->meta->code) || $obj->meta->code != 200 ) {
		} else {
			foreach( $obj->response->checkins->items as $item ) {
				$num += 1;
				// 各データの整理
				$id = $item->id;						// チェックインID
				$venue_id = $item->venue->id;			// ベニューのID
				$venue_name = preg_replace('/\&/', '＆', $item->venue->name);		// ベニューの名前
				$createdAt = $item->createdAt + $item->timeZoneOffset;		// チェックイン日時(オフセットと合わせる)
				$lat = $item->venue->location->lat;
				$lon = $item->venue->location->lng;
				$state = $item->venue->location->state;
				$city = $item->venue->location->city;
				$address = $item->venue->location->address;

				// 日時の整形
				$time = date( 'Y/m/d H:i', $createdAt );
				$start_time = date( 'Y/m/d', $createdAt );
/*
				// Google Map APIから標高を取得
					$request_url = 'https://maps.googleapis.com/maps/api/elevation/json?locations=' . $lat . ',' . $lon . '&key=' . $google_map_api_key;
					// cURLでリクエスト
					$curl = curl_init();
					curl_setopt( $curl, CURLOPT_URL, $request_url );
					curl_setopt( $curl, CURLOPT_HEADER, 1 ); 
					curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
					curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
					curl_setopt( $curl, CURLOPT_TIMEOUT, 5 );
					$res1 = curl_exec( $curl );
					$res2 = curl_getinfo( $curl );
					curl_close( $curl );
					$elevation_json = substr( $res1, $res2['header_size'] );
					$elevation_obj = json_decode( $elevation_json );
					foreach( $elevation_obj->results as $elev ) {
						$elevation = $elev->elevation;
					}
*/
				// KMLに出力
				$placemarkNode = $docNode->addChild( 'Placemark' );
				$placemarkNode->addChild( 'name', $venue_name );
//				$placemarkNode->addChild( 'description', $time . '&lt;br&gt;&lt;a href="swarm://checkins/' . $id . '"&gt;Open with Swarm&lt;/a&gt;');
				$placemarkNode->addChild( 'description', $time . '&lt;br&gt;&lt;a href="https://www.swarmapp.com/' . $user_id . '/checkin/' . $id . '"&gt;Open with Swarm&lt;/a&gt;');
				$placemarkNode->addChild( 'styleUrl', '#swarmap');
				$placemarkNode->addChild( 'address', $state . ' ' . $city . ' ' . $address);
				$lookAtNode = $placemarkNode->addChild( 'LookAt' );
				$lookAtNode->addChild( 'longitude', $lon );
				$lookAtNode->addChild( 'latitude', $lat );
				$lookAtNode->addChild( 'altitude', $elevation );
				$lookAtNode->addChild( 'range', '1000' );
				$lookAtNode->addChild( 'tilt', '0' );
				$lookAtNode->addChild( 'heading', '0' );
				$pointNode = $placemarkNode->addChild( 'Point' );
				$pointNode->addChild( 'coordinates', $lon . ',' . $lat . ',' . $elevation);
		}} $cnt +=  $lim;
	}
	$docNode->addChild( 'description', 'Swarm\'s checkin history. (' . $start_time . ' ~ ' . $end_time . ')');
	$dom = new DOMDocument( '1.0', 'UTF-8' );
	$dom->loadXML( $rootNode->asXML() );
	$dom->formatOutput = true;
//	header( 'Content-Type: text/xml; charset=utf-8' );
//	echo $dom->saveXML();
	$dom->save( 'swarmap.kml' );
	echo '<h1>Swarmap</h1><hr>';
	echo '<ul><li><h3>あなたのSwarm履歴から<a href="https://example.com/swarmap.kml">KMLファイル</a>を生成しました。' . $num . '地点書き込まれました。</h3></li></ul>';
	echo '<div><a href="https://example.com/">戻る</a></div>';
?>
