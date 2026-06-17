<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Sign In — Agentix</title>

  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/icons/favicon.svg') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            espresso:      { DEFAULT: '#4E2C23', light: '#6B3F33', dark: '#3A1F18' },
            'burnt-peach': { DEFAULT: '#E2725B', hover: '#D06048', light: '#FDE8E3' },
            'soft-apricot':{ DEFAULT: '#FFDAB9', light: '#FFF0E0' },
            bg:             { DEFAULT: '#FFF4EC', white: '#FFFFFF' },
          },
          fontFamily: {
            sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
          },
          borderRadius: {
            'sm': '8px',
            'md': '12px',
            'lg': '16px',
            'xl': '20px',
          },
        },
      },
    }
  </script>
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}">
</head>
<body style="background: linear-gradient(135deg, #FFF4EC 0%, #FFDAB9 40%, #FDE8E3 100%); min-height: 100vh;">

  <div style="
    display: flex;
    min-height: 100vh;
    align-items: center;
    justify-content: center;
    padding: 24px;
  ">
    {{-- Login Card Container --}}
    <div style="
      display: flex;
      width: 100%;
      max-width: 960px;
      min-height: 560px;
      background: #FFFFFF;
      border-radius: 20px;
      box-shadow: 0 24px 80px rgba(78, 44, 35, 0.12), 0 8px 24px rgba(78, 44, 35, 0.06);
      overflow: hidden;
    ">
      {{-- Left Panel — Branding --}}
      <div style="
        flex: 0 0 45%;
        background: linear-gradient(160deg, #4E2C23 0%, #6B3F33 60%, #3A1F18 100%);
        padding: 48px 40px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
      ">
        {{-- Decorative circles --}}
        <div style="
          position: absolute;
          top: -60px;
          right: -40px;
          width: 200px;
          height: 200px;
          border-radius: 50%;
          background: rgba(226, 114, 91, 0.15);
        "></div>
        <div style="
          position: absolute;
          bottom: -80px;
          left: -60px;
          width: 280px;
          height: 280px;
          border-radius: 50%;
          background: rgba(255, 218, 185, 0.08);
        "></div>
        <div style="
          position: absolute;
          top: 50%;
          right: 20px;
          width: 80px;
          height: 80px;
          border-radius: 50%;
          background: rgba(226, 114, 91, 0.1);
        "></div>

        {{-- Logo & Brand --}}
        <div style="position: relative; z-index: 1;">
          <div style="
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
          ">
            <div style="
              width: 44px;
              height: 44px;
              background: #E2725B;
              border-radius: 12px;
              display: flex;
              align-items: center;
              justify-content: center;
              flex-shrink: 0;
            ">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
              </svg>
            </div>
            <span style="
              font-size: 22px;
              font-weight: 700;
              color: #FFFFFF;
              letter-spacing: -0.3px;
            ">Agentix</span>
          </div>

          <h1 style="
            font-size: 28px;
            font-weight: 700;
            color: #FFFFFF;
            line-height: 1.3;
            margin-bottom: 12px;
            letter-spacing: -0.4px;
          ">Welcome back</h1>
          <p style="
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
          ">Sign in to manage your AI agents and monitor fleet operations across your tenant.</p>
        </div>

        {{-- Footer quote --}}
        <div style="position: relative; z-index: 1;">
          <div style="
            display: flex;
            gap: 12px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
          ">
            <div style="
              width: 36px;
              height: 36px;
              border-radius: 50%;
              background: linear-gradient(135deg, #E2725B, #FFDAB9);
              display: flex;
              align-items: center;
              justify-content: center;
              flex-shrink: 0;
            ">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
              </svg>
            </div>
            <div>
              <div style="font-size: 13px; color: rgba(255, 255, 255, 0.75); font-weight: 500;">Multi-tenant ready</div>
              <div style="font-size: 11px; color: rgba(255, 255, 255, 0.4);">Your access is scoped to assigned customers automatically.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Right Panel — Login Form --}}
      <div style="
        flex: 1;
        padding: 48px 44px;
        display: flex;
        flex-direction: column;
        justify-content: center;
      ">
        <div style="max-width: 380px; width: 100%; margin: 0 auto;">
          <div style="margin-bottom: 36px;">
            <h2 style="
              font-size: 22px;
              font-weight: 700;
              color: #4E2C23;
              margin-bottom: 6px;
            ">Sign in to your account</h2>
            <p style="
              font-size: 13px;
              color: #A08980;
            ">Use your email or username to continue.</p>
          </div>

          <form method="POST" action="{{ route('login') }}" style="display: flex; flex-direction: column; gap: 20px;">
            @csrf

            {{-- Login (email or username) --}}
            <div>
              <label for="login" style="
                display: block;
                font-size: 13px;
                font-weight: 600;
                color: #4E2C23;
                margin-bottom: 6px;
              ">Email or Username</label>
              <div style="position: relative;">
                <div style="
                  position: absolute;
                  left: 12px;
                  top: 50%;
                  transform: translateY(-50%);
                  color: #A08980;
                  pointer-events: none;
                  display: flex;
                ">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                </div>
                <input
                  type="text"
                  name="login"
                  id="login"
                  value="{{ old('login') }}"
                  placeholder="you@example.com"
                  autocomplete="username"
                  required
                  autofocus
                  style="
                    width: 100%;
                    padding: 12px 12px 12px 38px;
                    border: 1.5px solid {{ $errors->has('login') ? '#D14343' : '#F0DDD0' }};
                    border-radius: 12px;
                    font-size: 14px;
                    font-family: inherit;
                    color: #4E2C23;
                    background: #FFFFFF;
                    outline: none;
                    transition: border-color 0.2s ease, box-shadow 0.2s ease;
                    box-sizing: border-box;
                  "
                  onfocus="this.style.borderColor='#E2725B'; this.style.boxShadow='0 0 0 3px rgba(226,114,91,0.12)';"
                  onblur="this.style.borderColor='{{ $errors->has('login') ? '#D14343' : '#F0DDD0' }}'; this.style.boxShadow='none';"
                >
              </div>
              @error('login')
                <p style="margin-top: 6px; font-size: 12px; color: #D14343; display: flex; align-items: center; gap: 4px;">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                  {{ $message }}
                </p>
              @enderror
            </div>

            {{-- Password --}}
            <div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <label for="password" style="
                  font-size: 13px;
                  font-weight: 600;
                  color: #4E2C23;
                ">Password</label>
              </div>
              <div style="position: relative;" x-data="{ show: false }" x-on:keydown.escape="show = false">
                <div style="
                  position: absolute;
                  left: 12px;
                  top: 50%;
                  transform: translateY(-50%);
                  color: #A08980;
                  pointer-events: none;
                  display: flex;
                ">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                  </svg>
                </div>
                <input
                  type="password"
                  name="password"
                  id="password"
                  placeholder="Enter your password"
                  autocomplete="current-password"
                  required
                  style="
                    width: 100%;
                    padding: 12px 40px 12px 38px;
                    border: 1.5px solid {{ $errors->has('password') ? '#D14343' : '#F0DDD0' }};
                    border-radius: 12px;
                    font-size: 14px;
                    font-family: inherit;
                    color: #4E2C23;
                    background: #FFFFFF;
                    outline: none;
                    transition: border-color 0.2s ease, box-shadow 0.2s ease;
                    box-sizing: border-box;
                  "
                  onfocus="this.style.borderColor='#E2725B'; this.style.boxShadow='0 0 0 3px rgba(226,114,91,0.12)';"
                  onblur="this.style.borderColor='{{ $errors->has('password') ? '#D14343' : '#F0DDD0' }}'; this.style.boxShadow='none';"
                >
                <button type="button" onclick="
                  var p = document.getElementById('password');
                  p.type = p.type === 'password' ? 'text' : 'password';
                " style="
                  position: absolute;
                  right: 8px;
                  top: 50%;
                  transform: translateY(-50%);
                  background: none;
                  border: none;
                  cursor: pointer;
                  color: #A08980;
                  padding: 6px;
                  display: flex;
                  border-radius: 6px;
                  transition: color 0.15s ease;
                " onmouseover="this.style.color='#4E2C23'" onmouseout="this.style.color='#A08980'">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
              </div>
              @error('password')
                <p style="margin-top: 6px; font-size: 12px; color: #D14343; display: flex; align-items: center; gap: 4px;">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                  {{ $message }}
                </p>
              @enderror
            </div>

            {{-- Remember Me --}}
            <div style="display: flex; align-items: center; justify-content: space-between;">
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input
                  type="checkbox"
                  name="remember"
                  value="1"
                  style="
                    width: 16px;
                    height: 16px;
                    accent-color: #E2725B;
                    border-radius: 4px;
                    cursor: pointer;
                  "
                >
                <span style="font-size: 13px; color: #7A5C53; font-weight: 500;">Remember me</span>
              </label>
            </div>

            {{-- Submit Button --}}
            <button type="submit" style="
              width: 100%;
              padding: 13px;
              background: linear-gradient(135deg, #E2725B 0%, #D06048 100%);
              color: #FFFFFF;
              border: none;
              border-radius: 12px;
              font-size: 15px;
              font-weight: 600;
              font-family: inherit;
              cursor: pointer;
              margin-top: 4px;
              transition: transform 0.15s ease, box-shadow 0.2s ease;
              display: flex;
              align-items: center;
              justify-content: center;
              gap: 8px;
            " onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 20px rgba(226,114,91,0.35)';"
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(226,114,91,0.2)';"
               style="box-shadow: 0 2px 8px rgba(226,114,91,0.2);"
            >
              <span>Sign In</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M5 12h14"/>
                <polyline points="12 5 19 12 12 19"/>
              </svg>
            </button>
          </form>

          {{-- Divider --}}
          <div style="
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 28px 0 24px;
          ">
            <div style="flex: 1; height: 1px; background: #F0DDD0;"></div>
            <span style="font-size: 11px; color: #A08980; font-weight: 500; white-space: nowrap;">AGENTIX PLATFORM</span>
            <div style="flex: 1; height: 1px; background: #F0DDD0;"></div>
          </div>

          {{-- Feature pills --}}
          <div style="display: flex; flex-wrap: wrap; gap: 8px; justify-content: center;">
            <span style="
              font-size: 11px;
              color: #7A5C53;
              background: #FFF4EC;
              padding: 6px 14px;
              border-radius: 20px;
              font-weight: 500;
              display: flex;
              align-items: center;
              gap: 5px;
            ">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#E2725B" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Fleet Tracking
            </span>
            <span style="
              font-size: 11px;
              color: #7A5C53;
              background: #FFF4EC;
              padding: 6px 14px;
              border-radius: 20px;
              font-weight: 500;
              display: flex;
              align-items: center;
              gap: 5px;
            ">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#E2725B" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Multi-Tenant
            </span>
            <span style="
              font-size: 11px;
              color: #7A5C53;
              background: #FFF4EC;
              padding: 6px 14px;
              border-radius: 20px;
              font-weight: 500;
              display: flex;
              align-items: center;
              gap: 5px;
            ">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#E2725B" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Real-time Data
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
