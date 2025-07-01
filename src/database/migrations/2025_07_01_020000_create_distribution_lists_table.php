use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionListsTable extends Migration
{
    public function up()
    {
        Schema::create('distribution_lists', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('email_address')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('distribution_lists');
    }
}
