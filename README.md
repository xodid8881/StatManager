# StatManager


PocketMine Plugin

서버에 스탯을 구현시켜줍니다.

![KakaoTalk_20200903_205723331_01](https://user-images.githubusercontent.com/26338400/92186465-d20da680-ee91-11ea-9a60-dfb2f70af330.png)


API 를 이용한 스탯포인트 추가하기

use StatManager\StatManager;

/* 플러그인을 적용 뒤 연동할 플러그인에 use 를 추가해줍니다. */



그뒤 특정 이벤트에 아래 이벤트문을 적어줍니다.



StatManager::getInstance ()->GivePoint ($player,1);

/* $player 에게 1 개의 스탯 포인트를 추가해줍니다. */
