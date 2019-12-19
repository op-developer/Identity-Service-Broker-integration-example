<form action="<?= $example['action'] ?>" method="POST" class="example">
    <h2><?= $example['title'] ?></h2>

    <div class="row">
        <div class="info">
            <?php if ($example['embedded']) : ?>
                <p>
                    Alternatively you can embed the indentification UI into your service and render it as you like. The
                    end-user is redirected straight from your UI to the selected Identity Provider (bank, Mobile ID)
                    instead of OP's hosted UI.
                </p>
            <?php else : ?>
                <p>
                    You can place a button or link or some other call-to-action into your UI which redirects the end-user
                    to OP’s hosted identification UI along with the Open ID Connect authentication request.
                </p>
            <?php endif; ?>
            <p>
                After identifying herself the end-user is redirected back to the return_uri you specify in the
                authentication request. The consent can be set in the request as well.
            </p>
            <p><a href="https://github.com/op-developer/Identity-Service-Broker-API" target="_blank">
                See the API docs for more information.
            </a></p>
        </div>
        <section class="params">
            <h3>Parameters for the identification request:</h3>
            <ul class="param-group">
                <li>
                    <input type="checkbox" id="promptBox" name="promptBox" value="consent">
                    <label for="promptBox">
                        Require consent
                        <br/>
                        <small>End-user is forced to review her personal data before returning to service.</small>
                    </label>
                </li>
            </ul>
            <ul class="param-group">
                <li>
                    <input type="radio" name="purpose" value="normal" id="idBasic" checked>
                    <label for="idBasic">Basic identification</label>
                </li>
                <li>
                    <input type="radio" name="purpose" value="weak" id="idWeak">
                    <label for="idWeak">New weak credentials</label>
                </li>
                <li>
                    <input type="radio" name="purpose" value="strong" id="idStrong">
                    <label for="idStrong">New strong credentials</label>
                </li>
            </ul>
            <input type="hidden" id="authenticate" name="authenticate" value="start">
        </section>
    </div>

    <?php if ($example['embedded']) : ?>
        <div class="note">
            <p>Note that it’s mandatory to display the following texts in the UI even if you embed it into you service:</p>
            <ul>
                <li><?= $embeddedInfo['isbConsent'] ?></li>
                <li><?= $embeddedInfo['isbProviderInfo'] ?></li>
            </ul>
        </div>
    <?php endif; ?>

    <h3 class="view-title">Example UI <?= $example['id'] . ': ' . $example['name'] ?></h3>

    <div class="view"><div class="view-layout"><div class="view-main">

        <?php if ($example['id'] == 1) : ?>

          <button type="submit" class="button" value="Submit">Identify yourself</button>

        <?php elseif ($example['id'] == 2) : ?>

            <p><?= $embeddedInfo['isbConsent'] ?></p>
            <?php if ($embeddedInfo['disturbanceInfo']) : ?>
                <div class="alert -info">
                    <h3 class="disturbanceTitle"> <?= $embeddedInfo['disturbanceInfo']['header'] ?> </h3>
                    <div class="disturbanceMessage"><?= $embeddedInfo['disturbanceInfo']['text'] ?></div>
                </div>
                <p></p>
            <?php endif; ?>
                <div class="idp-buttons">
                    <?php foreach ($embeddedInfo ['identityProviders'] as $idp) { ?>
                        <div class="idp-button">
                            <button type="submit"
                                class="id-button"
                                name="selectedIdp"
                                id="<?= $idp['ftn_idp_id'] ?>"
                                value="<?= $idp['ftn_idp_id'] ?>" >
                                <img src="<?= $idp['imageUrl'] ?>" alt="<?= $idp['name'] ?>"/>
                            </button>
                        </div>
                        <?php
                    } ?>
                </div>
            <p><?= $embeddedInfo['isbProviderInfo'] ?></p>

        <?php elseif ($example['id'] == 3) : ?>

            <p><?= $embeddedInfo['isbConsent'] ?></p>
            <?php if ($embeddedInfo['disturbanceInfo']) : ?>
                <div class="alert -info">
                    <h3 class="disturbanceTitle"> <?= $embeddedInfo['disturbanceInfo']['header'] ?> </h3>
                    <div class="disturbanceMessage"><?= $embeddedInfo['disturbanceInfo']['text'] ?></div>
                </div>
                <p></p>
            <?php endif; ?>
                <select name="selectedIdp" onchange="this.form.submit();this.form.reset()">
                    <option disabled selected>Select Identity Provider</option>
                    <?php foreach ($embeddedInfo['identityProviders'] as $idp) { ?>
                        <option value="<?= $idp['ftn_idp_id'] ?>"><?= $idp['name'] ?></option>
                    <?php } ?>
                </select>
            <p><?= $embeddedInfo['isbProviderInfo'] ?></p>

        <?php endif; ?>

    </div></div></div>
</form>
