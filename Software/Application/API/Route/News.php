<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Router\ResponseCode;

class News extends \Grepodata\Library\Router\BaseRoute
{
  public static function NewsGET()
  {
    // TODO: get from database once more news posts are made
    $aNewsItems = array(
      array(
        'title' => 'City Indexer Update & Heatmap Removal',
        'date' => 'April 18, 2021',
        'author' => 'admin@grepodata.com',
        'short_text' => 'GrepoData started as a community project all the way back in 2016. Back then, it was just a small tool for my own alliance.
        After a while, people started requesting their own \'index\' to collect intel on other worlds they played on. I would manually create an empty copy of the database for them.
        Fast forward a couple of years, and the process is now automated to a point where anyone is able to create and share an index.
        Today, we released a big update to improve the security of our users and the intel they collect.',
        'full_text' => '
        <p class="text-center">
          GrepoData started as a community project all the way back in 2016. Back then, it was just a small tool for my own alliance.
          After a while, people started requesting their own \'index\' to collect intel on other worlds they played on. I would manually create an empty copy of the database for them.
          Fast forward a couple of years, and the process is now automated to a point where anyone is able to create and share an index.
          Today, we released a big update to improve the security of our users and the intel they collect.
        </p>
        <h3 class="gd-primary">The Problem</h3>
        <p>
          The biggest problem with the city indexer tool has always been that anyone with the url of the index was able to view the intel. As an owner/admin of an index,
          you had no control over who was viewing and contributing to your index. After all, if a player leaves your alliance, you would want them to leave your index as well.
          Another bad design choice we made is that each index required a new unique userscript. This should always just have been a single script that is the same for every user.
        </p>
        <h3 class="gd-primary">The Update</h3>
        <h5><strong>Authentication</strong></h5>
        <p>
          In an attempt to get approval of our tool, we had to improve the security of the index system.
          For this reason, we have now added a register/login screen to all interactions with the city indexer tool.
          If you want to continue using the city indexer, you have to register for a GrepoData account.
          This way, the owner/admin of an index (we renamed \'index\' to \'team\') will always have control over who has access to the intel.
          Users can be invited to join a team, and they can always be removed from that team at any time.
          Simply having a url to an index will no longer give you access to the intel.
        </p>
        <h5><strong>Activity Heatmap</strong></h5>
        <p>
          An additional requirement for approval was that we remove the activity heatmap feature from our website.
          The heatmap showed at which time a player gained attack points within the game.
          Due to privacy concerns, we had to remove the heatmap feature if we want to pursue approval of the city indexer script.
          We understand that many people will miss this feature, and we apologize for having to remove it.
          That being said, the heatmap was never an accurate representation of player activity and you are often better of by using your personal intuition.
        </p>
        <h5><strong>Backwards Compatibility</strong></h5>
        <p>
          All indexes created before April 18 2021 are still available to the users that created or used the old index system.
          If you created an old index, please register using the same email address that you used to create the index. Once you confirm your address, you will be registered as the owner of the old index.
          The old index URL will redirect to the intel you collected, but only if you have a GrepoData account. The owner of the old index can disable this redirect function for their team by navigating to the team settings.
        </p>
        <h5><strong>Userscript</strong></h5>
        <p>
          Finally, the userscript now requires you to link your GrepoData account. To do this, simply click the activation link in the pop-up that appears when you install the userscript.
          This way, the intel you collect is only available to your personal account and to the teams that you choose to share your intel with.
          It is no longer required to have multiple userscripts installed; you only need to install the script once.
        </p>
        <h3 class="gd-primary">A Final Note</h3>
        <p>
          With slightly over a thousand daily users, over 2 million unique reports indexed and almost 8000 indexes created, it has been a pleasure to provide this tool to the community.
          We hope to continue offering our service for as long as people are using it. If you are interested in contributing to the project, please check out our <a href="https://github.com/GrepoDataTools" target="_blank">GitHub</a> page.

          <br/>
          <br/>
          <strong>If you have any questions or remarks regarding this update, feel free to contact us.</strong>
        </p>
        <p>
          <br/>
          Thank you for using GrepoData.
          <br/>
          <br/>
          Yours sincerely,
          <br/>admin@grepodata.com
        </p>'
      )
    );

    $aResponse = array(
      'count' => count($aNewsItems),
      'items' => $aNewsItems
    );

    ResponseCode::success($aResponse);
  }

}
