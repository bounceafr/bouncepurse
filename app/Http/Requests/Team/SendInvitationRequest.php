<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\Enums\InvitationStatus;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->ownedTeam !== null && $user->id === $user->ownedTeam->user_id;
    }

    /** @return array<string, array<int, ValidationRule|array<mixed>|string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            $team = $user !== null ? $user->ownedTeam : null;

            if (! $team) {
                return;
            }

            if ($team->isFull()) {
                $validator->errors()->add('email', 'This team has reached the maximum of '.Team::MAX_MEMBERS.' members.');
            }

            if ($team->members()->where('users.email', $this->input('email'))->exists()) {
                $validator->errors()->add('email', 'This user is already a member of your team.');
            }

            $hasPendingInvite = TeamInvitation::query()
                ->where('team_id', $team->id)
                ->where('email', $this->input('email'))
                ->where('status', InvitationStatus::Pending)
                ->exists();

            if ($hasPendingInvite) {
                $validator->errors()->add('email', 'A pending invitation already exists for this email.');
            }
        });
    }
}
