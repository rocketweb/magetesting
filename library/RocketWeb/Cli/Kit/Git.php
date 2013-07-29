<?php

class RocketWeb_Cli_Kit_Git
    extends RocketWeb_Cli_Query
{
    public function init()
    {
        return $this->append('git init');
    }

    public function addAll()
    {
        return $this->append('git add -A');
    }

    public function commit($message)
    {
        return $this->append('git commit -m ?', $message);
    }

    /**
     * @param string $revision - hash (sha1{7,})
     * @param string $output - path to output file
     */
    public function deploy($revision, $output)
    {
        $this->append(
            'git archive --format zip --output ? ? `:subqury`',
            array($output, $revision)
        );
        $this->bindAssoc(
            ':subquery',
            $this->newQuery('git diff ? ?~1 --name-only', array($revision, $revision)),
            false
        );
        return $this;
    }

    public function rollback($revision)
    {
        return $this->append('git revert ? --no-edit', $revision);
    }
}