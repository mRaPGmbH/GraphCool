<?php

namespace Mrap\GraphCool\Tests\Utils;

use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\DB;

use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Utils\FileImport;
use Mrap\GraphCool\Tests\TestCase;

class FileImportTest extends TestCase
{

    protected $csv = '77u/RmFtaWxpZW5uYW1lLGlkCnRlc3QsMTIzCk5hbWUsMzQ1Cg==';
    protected $csv2 = '77u/RmFtaWxpZW5uYW1lLGlkCnRlc3QsMTIzCk5hbWUsCg==';
    protected $csv3 = '77u/RmFtaWxpZW5uYW1lLGlkLERhdHVtLFphaGwKdGVzdCwxMjMsMjAyMC0wMS0wMSwxLjIzNAoK';
    protected $csv4 = '77u/RmFtaWxpZW5uYW1lLGlkLERhdHVtLFphaGwKdGVzdCwxMjMsbm90LWEtZGF0ZSwxLjIzNAoK';
    protected $csvExcel = 'RmFtaWxpZW5uYW1lO2lkCnRlc3Q7MTIzCk5hbWU7MzQ1Cg==';
    protected $xlsx = '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';
    protected $ods = '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';
    protected $pdf = 'JVBERi0xLjUKJcOkw7zDtsOfCjIgMCBvYmoKPDwvTGVuZ3RoIDMgMCBSL0ZpbHRlci9GbGF0ZURlY29kZT4+CnN0cmVhbQp4nCXKuwrCQBBG4X6e4q+FjDOTbHYWli0CpkgXGEghdl46wTS+voqc6oMjrHjTCwJhMUcqiS0n+KDso2K/0XbA83982x80BaWRHTn3XLwgrjjOCjXE/VxFm1WxplX61tnPl1joFLTSig8XpBemCmVuZHN0cmVhbQplbmRvYmoKCjMgMCBvYmoKMTA5CmVuZG9iagoKNSAwIG9iago8PC9MZW5ndGggNiAwIFIvRmlsdGVyL0ZsYXRlRGVjb2RlL0xlbmd0aDEgNzc5Mj4+CnN0cmVhbQp4nOU3e2wb532/746UqCcpRZJl0xY/5iLZepGSaLuWY1m0JFKSJVvUgw7pl3giTyIT8RGSkmOnQdRtSQw6XlynS+bEQFpgDdIug09R1ilFZqvr0q3o2rQLiiJNvBpYi/0xG/bSJBvaxdrv++4ky46TYMP+60nf3e/9/j7eZdMzChTDHIjgDsflVAUxCADwTwCkPDybpR1DlfcjfAVA+OfJ1FT8hb85/CGA4XWA/Nenpo9PfuMH6e8AFEeRPxhV5Mg7LRcbAEqRD9ujSNh783g+4lcRvy8azz6ySDaWApgtiFumk2H5IiQI4hTxwrj8SMpuaEf/5ibEaUKOK//1te9HEB8EKMqkkplsBE4uA2xYYvxUWkkNvjDxFuIYn3gGaQT/2FWMYB7DBRH+oC/jaaiEPmMHmCHF77dd4quwHs4BLLP+rLnfHFz+3f9nFCbt8efwMrwOp+FdOKIzvOCDGMwgZe31PfgZUtnlg4Pwbch9htlXYRH5mlwInmGZ3PXywfOwAP9wmxcfxOFRjOWv4V3SCj/EUUnCB8QEX4G30OoHSNt3N1MCTi9McnByDfU9eFE4BXuFXyNyjnEEp2CBv4fz5ChazmKep1cz3vUpo0/BY3gfhSjMIswvY8d//xIKln+LWT0Ge+GPYA9Mr9F4k7wkFmL/xuAlrOn3OM25wszvEx8UviMInzyLyFdhCpdMMHfhtLjnMyr0v75EP5SQerEWCu7GFbaC+ebvhLblD8X7oBD8yzdWaMsDy78V5ZsJw7hho7HD8KPP85H3VUMctWH5NzcfvRkx7je+jN16BcDde+hgMOAfGx0Z9g3t3zc4sLe/r9fr6enu2uPu3N2x6/6d7Tu+tH1ba4vT0dy0ZXNd7X3SvXZbdUWZxVxaUlRYYMrPMxpEgUATVUnIo4q1tMwrSx5J7mtuop7qaE9zk0fyhlQqUxUfhjqpr4+TJFmlIarW4UNeQw6pbpScvEPSrUm6VyWJhe6CXcyFRNUf90h0kRwcDiB8ukcKUvUah/dx2FDHkRJE7HbU4FGxaKlH9c5Gc54Qxkjmiwq7pW6lsLkJ5guLECxCSN0ipebJlt2EA8IWz855AUwlzC1m6pEjqm844Omx2u3B5qZ+tVTq4Szo5ibVvG41n5ukMRY6nKLzTUu5pxctMBFqLI5IEflwQBVl1M2JnlzuKbWsUa2XetT6E7+uxswVtUnq8aiNzOrAyKqfgVsuiWqstUg09xFgOtK1q7dTZJ2SV2v5CBioCt0qGQnY2WX1Yq1zOa9EvblQTl5cnpuQqEXKzRcX51IeLDf4Amhicfm7p6yq9+mgaglFyc6gnrp3ZEC9Z/hQQBVqvTQqIwX/OyX7Dqu9bFXG91lswLJgcbDCdjsrw6lFN0wgos4NBzScwoT1NXA7G4OqEGKcpRVOpZ9x5lY4q+ohCXs7MBrIqYba/ojkwYqfktW5CZyuB1ljJIta+rHVLuXKy2i7M8hlKUbVH4lR1ViHRUKttQo4N0wlZ+FI6cfa45oVHdSVldN2Cc0wOx7JE9L/Z6PVaIBiofsatUEYC6juHgTcst4xz3yLEzXkEDYs1sObqTqllFohda12l4XliY0GuIquplZ0qxAK61qq08P3FfXkQj1aCMyWNBx4A1zLV+a3UuuCC7ZCsIcJV3XjlNV5coHIpGoLWSO47yZpwGpX3UHscFAKKEE2dlih+itWPhxBPitjgYFRaWD4YGCHHojGYOYMtZ47zEgBq2YGB1A11ZpoQLCKQRS0IIF6EZC6duFdza814bJgwTmVDW7XLhogVliRxjDUeupRenQ5ht9m1MjGqbtvxVoeQ9FOd5/VHrRrV3OTgGyqO0YNEytq3woLjylkmHA+u/s4idWymg09DUiKFJSiVHX7Aiw3Vh5eZb0YvOZ6r8Zuw9YUC8sEdmSvIKyYqrfRura4ai/HV9G+O9j9K2yaM0kDozlmXNINAkberwIbYfeOMis/C9iGlvDspRbc0nxD5+bdbraZozuZEak/kpNGA7u4NJ4nj1lPMF/lMEAGxrqam/Bo65qXyMnheTc5OXow8IYF3wtPjgVeE4jQHeoKzt+HvMAbFH80OFVgVEZkCGUIszSCiInLW99wA8xxroETOB5eJMBpphUagfCioNEsmqM67sgNAnIMGse9Im1AmkmjzXEav+aBlcxdaHSb3AXuYqFEsM4TRnoNKd/F99gCAgvFpIRY51FrhJMXydx8gduqScyhhFuL8KT/lmv/wcBCMf46W/kdHXWxC8elOorNxp8VD42wQflyMJoLBdlmgypsDf4TlUi7sU3Sbgwkr1gtlJQutUjqYvRORu/U6HmMno8jSqoIqs9h730qYRNwKGDHLUk3/NCas1xjnQrioZKz/KYZK1axfFVoNnwFqqDXvbmwtDT/HlFcV20oLir2BQvyi8wVAGXDQah6qZqo1aSzmjiryZEjR9LQ2VgGrupOl4s9y8pJe3l7W1uZq7XFeG/dtjJpWydxVboqpbKKKlfblypLCdkfGn/0MaXzF7+4v2XnqPQnFekp4dnmzT//+dgnj+/psuyptrFXFPAtXxW94lv4frwRTrsPrifEvMFUaa7cVLMefEHzett6oVhcv764vLzKFyy3FBuHg8VVSzVErSFfryFnashcDUnVkFAN8dUQqCG78eGuIS01hNYQSw25weVQ6OGHH06z6+iRlQtTgmpMqxzaq53jR480sqzay1yuMhfLi1RW1BBX23aWjHRvXdnW7S5aVknuzau0b60jho7Hp7Z/raXlmwfe+9FPLpHYzeejSXL2MHm3PHfOV160w+a4Sowff3BzcoScf+UvFs6xr6IxrP07mOsWCLq32vMrNpRABdQ3lNjFdetqfEHrOotY5Avmi1VzDSTVQEINxNdAaAO50EDGG8hQA2vEw+yCThcL3cVjb78VNou6Ig+D3bzNtQ77sG2rkziEbRh527pKaXOdhMFXVK2rEYV35v/K+62W5taBR/7uXFA53PatM1MvOhu2pYf9+/Y/e7BTIqanz2wq/7c/7nn5xNZN9p6w98vP2H4cd/p62vdvaHN0HwDg33jC+nOdfxmwjpt3fQQ27fviH3t++pNbb4+suzht7OND0Emol2+/6YEHVoXIHa+chrx2NN0OFeJp8ImbYIxT++AiqdOlDVCv2xPAgu/chxH4vvgD/H5m3BqSWLV5YNU+QckDOixAPn4faLAIVvwK0WADypzUYSOU4LeSBufhN9s3dTgfTuD3kwaboII4dLgASkmXDheSBPHpcBFsFC6ufhE7hF/qcAlsE006XAobxA4WvYG9yb8qPqDDBKhB1GEBSg2SDouw3dCqwwaUmdJhI2wwPKXDeVBj+IYO58OHhks6bIItxgUdLoCNxvd0uFB43/ifOlwEO0zv6HAxHC4o0uESeLBgxVcpbC34WU9sKpaNnVAiNCJnZRpOpo6nY1PRLN0SrqdtLa0ttDeZnJpWaHcynUqm5WwsmXAUdt8p1kZH0ESfnG2i/YmwYzA2oWiydFRJxyZHlKmZaTm9JxNWEhElTZvpnRJ34geUdIYhbY5WR8st5p2ysQx+XWTTckSJy+mHaHLy9jhoWpmKZbJKGomxBPU7Rh3UJ2eVRJbKiQgdW1UcmpyMhRVODCvprIzCyWwUI31wJh3LRGJh5i3jWE1gTTVGs8qsQvfJ2aySSSa65Az6wsjGYolkpokei8bCUXpMztCIkolNJZA5cZzerkORK2MuiURyFk3OKk0Y92RayURjiSmaYSnr2jQblbMs6biSTcfC8vT0cWxZPIVaE9ijY7FsFB3HlQzdrxyjI8m4nPi2QwsFazOJNaWxeCqdnOUxNmfCaUVJoDM5Ik/EpmNZtBaV03IYK4Zli4UzvCJYCJqSE82emXQypWCkD/QO3hLEALVqZpLTs+iZSScUJcI8YtizyjQqoePpZPIhls9kMo2BRrLR5jWRTyYTWVRNUjkSwcSxWsnwTJz1CcucXQlODqeTyEtNy1m0Es84otlsaqfTeezYMYestyaMnXGgZefn8bLHU4rejzSzEp8exPYnWOtmeH9ZEqP9g3QohfXxYnBUF2iiK5PZ6mjVXWAZY6lsxpGJTTuS6SnnkHcQeiAGU7iyuE6AAhGguGTEZYTCkIQUHIc0l4oileKPShgPRQpt0AKtuCj0olQS+dOoT6Eb4TRqsbvM7SYhAQ78tO/+QmttCI3oUfRx7SaE+lE/jBYGUW8CuWvtUhjllBges0xzCmYwDhkpeyCDWgrKRLgEhWZcX2Tji/gHOJRZ5bRhXK24Wu6q+UV2Y2iJ8kpnOYdFGufRP4S0JOp9Xj0oyim8exnkKByLcKvMth8lRrmUj2uySmS5twSXGruLxyH0OIn6Yd7JFckwt80mQrOcRDiq1/RBrHeaRxDheiu5ZdDzpztw99kY5dHNcp/7OJ3hGc7rQjyj56XVbIxHkUQqq8UxjIT5jXJY5vWMcG02YwldcwKnjn6uH6rrynpfEtzHrB4l02nS6z3J7xnuN4E+KI9P6/Ltvimvk8yrrnU6jtwslw0jfRr/juu7LI5V0XxN6PvoGN+VUT3jOLdLYT8+j/GpSPK+Jez38h7fqoo2N5P6nFKum0I4ybNYqWMz7w3LROGRMkjmO38CNaa5by22KJ8OmfdW0Xud5Rms1CuiZ8qiTnFKM3j4XLD9rug1fQDPicG7WtQquHY2WU+mebyZNbYTPNrIao5atZnUtO5Jy3ian0cPrfZnks+bVtEIt9b8GTWf5LXJ6l6TPKII/mkd12YribozvB/aftKmOfupysm8vkldL8VPpaweS5zvjyifwBTsxBdLJ0bH/hx8DtfumrC+Zxx6zM7/sx6LK8UruHZ/pFdjiWOMg/ruT6zuupk1+3elE6N4Bg3y8yKlz49Xrxy9wwLbNXeema38zLw9C20aY4hneTwZXksHz2EK+UPoYZC9Q2tv+09gSHe55gt8eyaIAoREyRTcAzYSgv1kHPxkD3QQNz7xYx5fnv2kG3H2dJAOmEO5DqTvRnwX0u/Hs9OG905cQ7iewWXApUm0oIQTn04db0a8CTXexjvhi1E7kcqeexHvw2ev/vQi3YNPj473I45PCJF8fAnv5PdLxOBeIFc+IW9/Qugn5PHfE9/vydwHZz4Q/uNGve3CjUs3hKHr49cvXBdbrhPzdWKCa5Zrvmuha6lrX7+WV2i+Sorh30nZv17ZYftVx2X/v3S874fLmNnllsu+y3OX1cvGy0T0vy9W2SxLdKllKbU0t/TTpStLN5ZMcxfPXBT+9k2nzfym7U3BtjC08PiCGHqFmF+xvSL4Xgy9KJw5T8znbeed58UXzjls53prbM8/t9l25bkbzwmLy0sLz5WUed8kQ2QQOrCG+xfEZduFPZVkH6ZlxrsNlxPXEK4krmdw4TcPittwOcmge4c4/mek6Kz1bOPZR8+eOmtMPTn35JknxbknzjwhXJi9NCtkfPW2ZKLRluhtsK13VfvzXaI/D92gd3f/RO0Wb2jcbRtHoUMHW2wHe+tt97jK/UZM2ICCZtEmdopDYlJ8Rrwk5ptGfDW2YVxXfDd8gttXUOw1D9mGnEPi4vIVtzJgR2t7U3vn9or93npbX+8Om7nX1uvsfbv3V73Xe/PGe8lL+O+94L3kFd3eeqfX7a2xezf2Wf1Vrkp/GTH7LS6zXyDYaBf4neZls2A2j5sfN4tm6ARhrooYySI5Mz822tg4sJi/PDKgmnyHVHJSrR1ld/fwQTXvpAr+g4cC84T8afCJ06eha9OA2jYaUEObggNqBAE3A+YQsGyar4KuYCaTbeQXaWxEeAbv0DjTiMSjGY0Kq3xozJAMHlEZrkQamYCGE7w3Mh4SmB5B7aMZYDfGbNSUmHZGN8eVtRsHqo/+D6+nxtQKZW5kc3RyZWFtCmVuZG9iagoKNiAwIG9iago0NDE2CmVuZG9iagoKNyAwIG9iago8PC9UeXBlL0ZvbnREZXNjcmlwdG9yL0ZvbnROYW1lL0JBQUFBQStMaWJlcmF0aW9uU2VyaWYKL0ZsYWdzIDQKL0ZvbnRCQm94Wy01NDMgLTMwMyAxMjc3IDk4MV0vSXRhbGljQW5nbGUgMAovQXNjZW50IDg5MQovRGVzY2VudCAtMjE2Ci9DYXBIZWlnaHQgOTgxCi9TdGVtViA4MAovRm9udEZpbGUyIDUgMCBSCj4+CmVuZG9iagoKOCAwIG9iago8PC9MZW5ndGggMjM1L0ZpbHRlci9GbGF0ZURlY29kZT4+CnN0cmVhbQp4nF1Qu27EIBDs+YotL8UJbOfRWEini05ykYfi5AMwrB2kGBDGhf8+C74kUgrQDDOzGpafu8fO2cRfo9c9JhitMxEXv0aNMOBkHatqMFanKyu3nlVgnLL9tiScOzf6tmX8jbQlxQ0OJ+MHvGH8JRqM1k1w+Dj3xPs1hC+c0SUQTEowONKcJxWe1Yy8pI6dIdmm7UiRP8P7FhDqwqu9ivYGl6A0RuUmZK0QEtrLRTJ05p/W7Ilh1J8qkrMipxAPt5JwXfD9XcbN/t6UGVd3npa/+9MS9BojNSw7KdVyKevwd23Bh5wq5xve7nIWCmVuZHN0cmVhbQplbmRvYmoKCjkgMCBvYmoKPDwvVHlwZS9Gb250L1N1YnR5cGUvVHJ1ZVR5cGUvQmFzZUZvbnQvQkFBQUFBK0xpYmVyYXRpb25TZXJpZgovRmlyc3RDaGFyIDAKL0xhc3RDaGFyIDMKL1dpZHRoc1s3NzcgMjc3IDQ0MyAzODkgXQovRm9udERlc2NyaXB0b3IgNyAwIFIKL1RvVW5pY29kZSA4IDAgUgo+PgplbmRvYmoKCjEwIDAgb2JqCjw8L0YxIDkgMCBSCj4+CmVuZG9iagoKMTEgMCBvYmoKPDwvRm9udCAxMCAwIFIKL1Byb2NTZXRbL1BERi9UZXh0XQo+PgplbmRvYmoKCjEgMCBvYmoKPDwvVHlwZS9QYWdlL1BhcmVudCA0IDAgUi9SZXNvdXJjZXMgMTEgMCBSL01lZGlhQm94WzAgMCA1OTUuMzAzOTM3MDA3ODc0IDg0MS44ODk3NjM3Nzk1MjhdL0dyb3VwPDwvUy9UcmFuc3BhcmVuY3kvQ1MvRGV2aWNlUkdCL0kgdHJ1ZT4+L0NvbnRlbnRzIDIgMCBSPj4KZW5kb2JqCgo0IDAgb2JqCjw8L1R5cGUvUGFnZXMKL1Jlc291cmNlcyAxMSAwIFIKL01lZGlhQm94WyAwIDAgNTk1IDg0MSBdCi9LaWRzWyAxIDAgUiBdCi9Db3VudCAxPj4KZW5kb2JqCgoxMiAwIG9iago8PC9UeXBlL0NhdGFsb2cvUGFnZXMgNCAwIFIKL09wZW5BY3Rpb25bMSAwIFIgL1hZWiBudWxsIG51bGwgMF0KL0xhbmcoZW4tVVMpCj4+CmVuZG9iagoKMTMgMCBvYmoKPDwvQ3JlYXRvcjxGRUZGMDA1NzAwNzIwMDY5MDA3NDAwNjUwMDcyPgovUHJvZHVjZXI8RkVGRjAwNEMwMDY5MDA2MjAwNzIwMDY1MDA0RjAwNjYwMDY2MDA2OTAwNjMwMDY1MDAyMDAwMzYwMDJFMDAzND4KL0NyZWF0aW9uRGF0ZShEOjIwMjEwNjI4MDgwNTA4KzAyJzAwJyk+PgplbmRvYmoKCnhyZWYKMCAxNAowMDAwMDAwMDAwIDY1NTM1IGYgCjAwMDAwMDU0OTcgMDAwMDAgbiAKMDAwMDAwMDAxOSAwMDAwMCBuIAowMDAwMDAwMTk5IDAwMDAwIG4gCjAwMDAwMDU2NjYgMDAwMDAgbiAKMDAwMDAwMDIxOSAwMDAwMCBuIAowMDAwMDA0NzE5IDAwMDAwIG4gCjAwMDAwMDQ3NDAgMDAwMDAgbiAKMDAwMDAwNDkzNSAwMDAwMCBuIAowMDAwMDA1MjM5IDAwMDAwIG4gCjAwMDAwMDU0MTAgMDAwMDAgbiAKMDAwMDAwNTQ0MiAwMDAwMCBuIAowMDAwMDA1NzY1IDAwMDAwIG4gCjAwMDAwMDU4NjIgMDAwMDAgbiAKdHJhaWxlcgo8PC9TaXplIDE0L1Jvb3QgMTIgMCBSCi9JbmZvIDEzIDAgUgovSUQgWyA8RDRFMEUyQUQxODlEMjkyRjEzNDAzNDA1MkFCNThCQTQ+CjxENEUwRTJBRDE4OUQyOTJGMTM0MDM0MDUyQUI1OEJBND4gXQovRG9jQ2hlY2tzdW0gLzE2RkRCQjMxQjNDRTczNDc2NjFCQjM1QkY1M0NGRjFECj4+CnN0YXJ0eHJlZgo2MDM3CiUlRU9GCg==';

    protected $columns = [['column' => 'last_name','label' => 'Familienname'],['column' => 'id','label' => 'id']];
    protected $data = [['last_name' => 'test','id' => 123],['last_name' => 'Name','id' => 345]];

    protected $relationsCsv = 'aWQsRmFtaWxpZW5uYW1lLHBpdm90IHByb3BlcnR5CjEyMyxYLHRlc3QgQQozNDUsLAo=';
    protected $relationsColumns = [['column' => 'created_at','label' => 'should not import'],['column' => 'id','label' => 'id'],['column' => 'last_name','label' => 'Familienname']];
    protected $relationsEdgeColumns = ['belongs_to_many' => [['id' => '123-test-id', 'columns' => [['column' => '_pivot_property', 'label' => 'pivot property'],['column' => '_none', '-']]]]];
    protected $relationsData = [
        ['last_name' => 'X','id' => '123','belongs_to_many'=>['123-test-id'=>['where'=>['column'=>'id','operator'=>'=','value'=>'123-test-id'],'pivot_property'=>'test A']]],
        ['last_name' => null,'id' => '345','belongs_to_many'=>['123-test-id'=>['where'=>['column'=>'id','operator'=>'=','value'=>'123-test-id'],'pivot_property'=>null]]]
    ];

    public function testNix(): void
    {
        self::assertTrue(true);
    }


    public function testBasicCsv(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $import = new FileImport(1, 'DummyModel');
        $result = $import->importFile($this->csv, $this->columns, [], 0);
        self::assertEquals($this->data, $result, 'Imported data did not match csv contents.');
    }

    public function testBasicCsvExcel(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $import = new FileImport(1, 'DummyModel');
        $result = $import->importFile($this->csvExcel, $this->columns, [], 0);
        self::assertEquals($this->data, $result, 'Imported data did not match csv contents.');
    }

    public function testBasicXlsx(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $import = new FileImport(1, 'DummyModel');
        $result = $import->importFile($this->xlsx, $this->columns, [], 0);
        self::assertSame($this->data, $result, 'Imported data did not match xlsx contents.');
    }

    public function testBasicOds(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $import = new FileImport(1, 'DummyModel');
        $result = $import->importFile($this->ods, $this->columns, [], 0);
        self::assertSame($this->data, $result, 'Imported data did not match ods contents.');
    }

    public function testErrorWrongMimeType(): void
    {
        $this->expectException(Error::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $import = new FileImport(1, 'DummyModel');
        $import->importFile($this->pdf, $this->columns, [], 0);
    }

    public function testNoDataError(): void
    {
        $this->expectException(Error::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $import = new FileImport(1, 'DummyModel');
        $import->importFile(null, $this->columns, [], 0);
    }

    public function testInvalidMapError(): void
    {
        $this->expectException(Error::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $import = new FileImport(1, 'DummyModel');
        $_REQUEST['map'] = 'not a valid json';
        $import->importFile(null, $this->columns, [], 0);
    }

    public function testEmptyMapError(): void
    {
        $this->expectException(Error::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $import = new FileImport(1, 'DummyModel');
        $_REQUEST['map'] = '[]';
        $import->importFile(null, $this->columns, [], 0);
    }

    public function testNoFileError(): void
    {
        $this->expectException(Error::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $import = new FileImport(1, 'DummyModel');
        $_REQUEST['map'] = json_encode([
            0 => ['0.variables.file']
        ]);
        $import->importFile(null, $this->columns, [], 0);
    }

    public function testMultipartFileUpload(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $import = new FileImport(1, 'DummyModel');
        $_REQUEST['map'] = json_encode([
           0 => ['0.variables.file']
       ]);
        $filename = $this->dataPath().'/uploaded-file.csv';
        file_put_contents($filename, base64_decode($this->csv));
        $_FILES[0] = [
            'tmp_name' => $filename,
            'type' => 'application/octet-stream'
        ];
        $result = $import->importFile(null, $this->columns, [], 0);
        self::assertEquals($this->data, $result, 'Imported data did not match multipart uploaded file contents.');
        self::assertFalse(file_exists($filename), 'Uploaded file has not been automatically deleted after import.');
    }

    public function testRelations(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $import = new FileImport(1, 'DummyModel');
        $result = $import->importFile($this->relationsCsv, $this->relationsColumns, $this->relationsEdgeColumns, 0);
        self::assertEquals($this->relationsData, $result, 'Imported relation data did not match csv contents.');
    }

    public function testImport(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $mock->expects($this->once())
            ->method('update')
            ->willReturn((object) ['id'=>123, 'last_name'=>'test']);
        $mock->expects($this->once())
            ->method('insert')
            ->willReturn((object) ['id'=>345, 'last_name'=>'Name']);

        DB::setProvider($mock);
        $import = new FileImport(1, 'DummyModel');

        $result = $import->import([
            'data_base64' => $this->csv2,
            'columns' => $this->columns,
            'belongs_to_many' => $this->relationsEdgeColumns['belongs_to_many']
        ]);
        $expected = (object)[
          'updated_rows' => 1,
          'updated_ids' => [123],
          'inserted_rows' => 1,
          'inserted_ids' => [345],
          'failed_rows' => 0,
          'failed_row_numbers' => [],
          'affected_rows' => 2,
          'affected_ids' => [345,123],
        ];
        self::assertEquals($expected, $result, 'Import did not return expected result.');
    }

    public function testFailedRows(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $mock->expects($this->once())
            ->method('update')
            ->willReturn(null);
        $mock->expects($this->once())
            ->method('insert')
            ->willReturn((object) ['id'=>345, 'last_name'=>'Name']);

        DB::setProvider($mock);
        $import = new FileImport(1, 'DummyModel');
        $result = $import->import([
            'data_base64' => $this->csv2,
            'columns' => $this->columns
        ]);
        $expected = (object)[
          'updated_rows' => 0,
          'updated_ids' => [],
          'inserted_rows' => 1,
          'inserted_ids' => [345],
          'failed_rows' => 1,
          'failed_row_numbers' => [2],
          'affected_rows' => 1,
          'affected_ids' => [345],
        ];
        self::assertEquals($expected, $result, 'Import did not return expected result.');
    }

    public function testFieldTypes():void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $mock->expects($this->once())
            ->method('update')
            ->willReturn((object) ['id'=>123, 'last_name'=>'Name', 'date' => 32123, 'float' => 1.123123]);

        DB::setProvider($mock);
        $import = new FileImport(1, 'DummyModel');

        $columns = $this->columns;
        $columns[] = ['column' => 'date', 'label' => 'Datum'];
        $columns[] = ['column' => 'float', 'label' => 'Zahl'];

        $result = $import->import([
            'data_base64' => $this->csv3,
            'columns' => $columns
        ]);
        $expected = (object)[
            'updated_rows' => 1,
            'updated_ids' => [123],
            'inserted_rows' => 0,
            'inserted_ids' => [],
            'failed_rows' => 0,
            'failed_row_numbers' => [],
            'affected_rows' => 1,
            'affected_ids' => [123],
        ];
        self::assertEquals($expected, $result, 'Import did not return expected result.');
    }

    public function testInvalidDates(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $mock->expects($this->once())
            ->method('update')
            ->willReturn((object) ['id'=>123, 'last_name'=>'Name', 'date' => 32123, 'float' => 1.123123]);

        DB::setProvider($mock);
        $import = new FileImport(1, 'DummyModel');

        $columns = $this->columns;
        $columns[] = ['column' => 'date', 'label' => 'Datum'];
        $columns[] = ['column' => 'float', 'label' => 'Zahl'];

        $result = $import->import([
            'data_base64' => $this->csv4,
            'columns' => $columns
        ]);
        $expected = (object)[
            'updated_rows' => 1,
            'updated_ids' => [123],
            'inserted_rows' => 0,
            'inserted_ids' => [],
            'failed_rows' => 0,
            'failed_row_numbers' => [],
            'affected_rows' => 1,
            'affected_ids' => [123],
        ];
        self::assertEquals($expected, $result, 'Import did not return expected result.');
    }



}