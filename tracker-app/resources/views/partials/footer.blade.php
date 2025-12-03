<div class="container pt-4 border-top"
     style="margin-top: 128px;">
  <div class="row text-center text-md-start">

    <!-- Clubs -->
    <div class="col-md-4 mb-3">
      <h5 class="mb-4 text-muted">Star Wars Clubs</h5>
      <ul class="list-unstyled">
        <li class="my-2">
          <a href="https://www.501st.com"
             target="_blank"
             rel="noopener"
             class="text-decoration-none">
            <img class="me-2 rounded-circle overflow-hidden"
                 src="{{ url('img/icons/501st-legion-128x128.png') }}"
                 height="32px"
                 width="32px" />
            501st Legion
          </a>
        </li>
        <li class="my-2">
          <a href="https://www.rebellegion.com"
             target="_blank"
             rel="noopener"
             class="text-decoration-none">
            <img class="me-2 rounded-circle overflow-hidden"
                 src="{{ url('img/icons/rebel-legion-128x128.png') }}"
                 height="32px"
                 width="32px" />
            Rebel Legion
          </a>
        </li>
        <li class="my-2">
          <a href="https://mandalorianmercs.org"
             target="_blank"
             rel="noopener"
             class="text-decoration-none">
            <img class="me-2 rounded-circle overflow-hidden"
                 src="{{ url('img/icons/mandalorian-mercs-128x128.png') }}"
                 height="32px"
                 width="32px" />
            Mandalorian Mercs
          </a>
        </li>
        <li class="my-2">
          <a href="https://www.thedarkempire.org"
             target="_blank"
             rel="noopener"
             class="text-decoration-none">
            <img class="me-2 rounded-circle overflow-hidden"
                 src="{{ url('img/icons/dark-empire-128x128.png') }}"
                 height="32px"
                 width="32px" />
            The Dark Empire
          </a>
        </li>
        <li class="my-2">
          <a href="https://www.astromech.net"
             target="_blank"
             rel="noopener"
             class="text-decoration-none">
            <img class="me-2 rounded-circle overflow-hidden"
                 src="{{ url('img/icons/droid-builders-128x128.png') }}"
                 height="32px"
                 width="32px" />
            Droid Builders
          </a>
        </li>
      </ul>
    </div>

    <!-- Code Contribution -->
    <div class="col-md-4 mb-3">
      <h5 class="mb-3 text-muted">Contribute</h5>
      <ul class="list-unstyled">
        <li class="my-2">
          <a href="https://github.com/obsidianslicers/trooper-tracker"
             target="_blank"
             rel="noopener"
             class="text-decoration-none">
            <i class="fa-brands fa-github me-2"></i>
            GitHub Repository
          </a>
        </li>
        <li class="my-2">
          <a href="https://github.com/obsidianslicers/trooper-tracker?tab=contributing-ov-file#readme"
             target="_blank"
             rel="noopener"
             class="text-decoration-none">
            <i class="fa-solid fa-code-branch me-2"></i>
            How to Contribute
          </a>
        </li>
        <li class="my-2">
          <a href="https://github.com/obsidianslicers/trooper-tracker/issues"
             target="_blank"
             rel="noopener"
             class="text-decoration-none">
            <i class="fa-solid fa-bug me-2"></i>
            Report Issues
          </a>
        </li>
      </ul>
    </div>

    <!-- Credits / Branding -->
    <div class="col-md-4 mb-3 text-center text-md-start">
      <h5 class="mb-3 text-muted">The Obsidian Slicers</h5>
      <ul class="list-unstyled">
        <li class="my-2">
          <a href="https://mattdrennan.com"
             class="fw-bold text-decoration-none">
            Matthew Drennan <span class="text-muted">(TK52233)</span>
          </a>
        </li>
        <li class="my-2">
          <a href="https://www.501st.com/members/displaymemberdetails.php?userID=48435"
             class="fw-bold text-decoration-none">
            Stu Ellerbusch <span class="text-muted">(IC51399)</span>
          </a>
        </li>
        <li class="mt-5">
          <p class="text-muted">
            <a href="https://github.com/obsidianslicers"
               target="_blank"
               rel="noopener">
              <img src="{{ url('img/icons/obsidian-slicers-64x64.png') }}"
                   alt="Obsidian Slicers logo"
                   class="img-fluid rounded mb-2 me-2 float-start">
            </a>
            The <strong>Obsidian Slicers</strong> maintain the code of the tracker, ensuring it stays lean,
            reliable, and future-proof for contributors.
          </p>
        </li>
      </ul>
    </div>

  </div>

  @if(Auth::check() && Auth::user() != null)
  <div class="container-fluid border-top mt-3">
    <div class="row align-items-center small text-muted py-2">
      <div class="col-md-6">
        Theme:
        <a href="{{ route('account.profile') }}">
          <strong>{{ to_title(Auth::user()->theme->name ?? \App\Enums\TrooperTheme::STORMTROOPER->name) }}</strong>
        </a>
      </div>
      <div class="col-md-6 text-md-end">
        Logged in as:
        <a href="{{ route('account.profile') }}">
          <strong>{{ Auth::user()->name }}</strong>
        </a>
      </div>
    </div>
  </div>
  @endif

</div>