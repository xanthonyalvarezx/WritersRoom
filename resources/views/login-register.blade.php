<x-main-layout :title="$mode === 'register' ? 'Register' : 'Login'">
    <section class="auth-panel auth-screen">
        @if ($mode === 'register')
            <div class="auth-card">
                <p class="eyebrow">Join the room</p>
                <h1>Register</h1>
                <form action="{{ route('register') }}" method="post">
                    @csrf
                    <label>
                        <span class="field-label">First name</span>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                    </label>
                    @error('first_name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    <label>
                        <span class="field-label">Last name</span>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                    </label>
                    @error('last_name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    <label>
                        <span class="field-label">Pen name <small>(optional)</small></span>
                        <input type="text" name="pen_name" value="{{ old('pen_name') }}">
                    </label>
                    @error('pen_name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    <label>
                        <span class="field-label">Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" required>
                    </label>
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    <label>
                        <span class="field-label">Password</span>
                        <input type="password" name="password" required>
                    </label>
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    <label>
                        <span class="field-label">Confirm password</span>
                        <input type="password" name="password_confirmation" required>
                    </label>
                    <button class="btn btn-ink" type="submit">Register</button>
                </form>
                <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Login</a></p>
            </div>
        @else
            <div class="auth-card">
                <p class="eyebrow">Welcome back</p>
                <h1>Login</h1>
                <form action="{{ route('login') }}" method="post">
                    @csrf
                    <label>
                        <span class="field-label">Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" required>
                    </label>
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    <label>
                        <span class="field-label">Password</span>
                        <input type="password" name="password" required>
                    </label>
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    <button class="btn btn-ink" type="submit">Login</button>
                </form>
                <p class="auth-switch">Don't have an account? <a href="{{ route('register') }}">Register</a></p>
            </div>
        @endif
    </section>
</x-main-layout>
