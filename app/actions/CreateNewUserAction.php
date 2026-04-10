namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Events\UserCreated;

class CreateNewUserAction
{
    public function execute(array $data): User
    {
        // 1. Simpan ke Database
        $user = User::create([
            'full_name'     => $data['full_name'],
            'username'      => $data['username'],
            'email'         => $data['email'] ?? null,
            'role'          => $data['role'],
            'password_hash' => Hash::make($data['password']), // Enkripsi password
        ]);

        // 2. Tembakkan Event (Memberi tahu sistem bahwa user baru saja dibuat)
        UserCreated::dispatch($user);

        return $user;
    }
}